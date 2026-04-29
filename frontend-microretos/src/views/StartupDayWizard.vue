<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api.js';

const route  = useRoute();
const router = useRouter();

const paso       = ref(1);
const totalPasos = 8;
const guardando  = ref(false);
const cargando   = ref(false);
const uuid       = ref(route.params.uuid || null);
const isLoaded   = ref(false);
const errorMsg   = ref('');

const familias   = ref([]);
const ciclos     = ref([]);
const modulos    = ref([]);
const empresas   = ref([]);
const centros    = ref([]);
const microretos = ref([]);

// Autocomplete desde microreto
const autocompletando    = ref(false);
const pendingCicloId     = ref(null);   // ciclo a seleccionar cuando carguen los ciclos del watch

// ── Sesiones ──────────────────────────────────────────────────────────────────
const sesiones           = ref([])
const sesionSeleccionada = ref(null)
const sesionBusqueda     = ref('')

const sesionesFiltradas = computed(() => {
  if (!sesionBusqueda.value.trim()) return sesiones.value
  const q = sesionBusqueda.value.trim().toLowerCase()
  return sesiones.value.filter(s =>
    (s.microreto_titulo || '').toLowerCase().includes(q) ||
    (s.centro_educativo || '').toLowerCase().includes(q) ||
    (s.ciclo_formativo  || '').toLowerCase().includes(q)
  )
})

const microretoVinculado = computed(() =>
  form.value.microreto_id
    ? microretos.value.find(m => m.id == form.value.microreto_id)
    : null
)

function formatFecha(isoDate) {
  if (!isoDate) return ''
  const d = new Date(isoDate + 'T12:00:00')
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' })
}

async function seleccionarSesion(s) {
  sesionSeleccionada.value = s
  form.value.sesion_id     = s.id
  form.value.microreto_id  = s.microreto_id
  if (s.microreto_id) {
    const mr = microretos.value.find(m => m.id == s.microreto_id)
    if (mr) await autocompletarDesdeMicroreto(mr)
  }
}

function limpiarSesion() {
  sesionSeleccionada.value = null
  sesionBusqueda.value     = ''
  form.value.sesion_id     = null
  form.value.microreto_id  = ''
  form.value.empresa_id    = ''
  form.value.centro_id     = ''
  form.value.familia_id    = ''
  form.value.ciclo_id      = ''
  form.value.curso         = ''
}

const form = ref({
  titulo: '', empresa_id: '', centro_id: '', familia_id: '', ciclo_id: '',
  curso: '', microreto_id: '',
  sesion_id: route.query.sesion_id ? Number(route.query.sesion_id) : null,
  datos_empresa: { nombre: '', cif: '', sector: '', actividad: '', persona_contacto: '', email: '', telefono: '', web: '', descripcion: '' },
  datos_centro: { nombre: '', municipio: '', docente_nombre: '', docente_email: '' },
  equipo: { alumnos: [], docente_responsable: '' },
  modulos_seleccionados: [],
  ra_ce: '',
  fundamentacion: { contexto: '', justificacion: '', innovacion: '' },
  diseno_reto: { descripcion: '', pregunta_reto: '', restricciones: '', entregables: '' },
  diseno_microproyecto: { fases: [], metodologia: '', cronograma: '' },
  resumen: { texto: '' },
  objetivos: { lista: [] },
  kpis: { lista: [] },
  estado: 'borrador',
});

// ── Helpers equipo ────────────────────────────────────────────────────────────
const nuevoAlumno = ref({ nombre: '', rol: '' });
function addAlumno() {
  if (!nuevoAlumno.value.nombre.trim()) return;
  form.value.equipo.alumnos.push({ ...nuevoAlumno.value });
  nuevoAlumno.value = { nombre: '', rol: '' };
}
function removeAlumno(i) { form.value.equipo.alumnos.splice(i, 1); }

// ── Helpers fases ────────────────────────────────────────────────────────────
const nuevaFase = ref({ nombre: '', descripcion: '', duracion: '' });
function addFase() {
  if (!nuevaFase.value.nombre.trim()) return;
  form.value.diseno_microproyecto.fases.push({ ...nuevaFase.value });
  nuevaFase.value = { nombre: '', descripcion: '', duracion: '' };
}
function removeFase(i) { form.value.diseno_microproyecto.fases.splice(i, 1); }

// ── Helpers listas ────────────────────────────────────────────────────────────
const nuevoObjetivo = ref('');
function addObjetivo() { if (!nuevoObjetivo.value.trim()) return; form.value.objetivos.lista.push(nuevoObjetivo.value.trim()); nuevoObjetivo.value = ''; }
function removeObjetivo(i) { form.value.objetivos.lista.splice(i, 1); }

const nuevoKpi = ref('');
function addKpi() { if (!nuevoKpi.value.trim()) return; form.value.kpis.lista.push(nuevoKpi.value.trim()); nuevoKpi.value = ''; }
function removeKpi(i) { form.value.kpis.lista.splice(i, 1); }

// ── Módulos ───────────────────────────────────────────────────────────────────
function toggleModulo(m) {
  const idx = form.value.modulos_seleccionados.findIndex(x => x.id === m.id);
  if (idx >= 0) form.value.modulos_seleccionados.splice(idx, 1);
  else form.value.modulos_seleccionados.push({ id: m.id, nombre: m.nombre });
}
function moduloSeleccionado(id) { return form.value.modulos_seleccionados.some(m => m.id === id); }

// ── Catálogos ─────────────────────────────────────────────────────────────────
async function cargarCatalogos() {
  const [rE, rC, rF, rM, rS] = await Promise.all([
    api.get('/empresas'), api.get('/centros'),
    api.get('/familias'), api.get('/microretos'),
    api.get('/sesiones'),
  ]);
  empresas.value   = rE.data;
  centros.value    = rC.data;
  familias.value   = rF.data;
  microretos.value = rM.data;
  sesiones.value   = rS.data;
}

watch(() => form.value.familia_id, async (id) => {
  ciclos.value  = []; modulos.value = [];
  form.value.ciclo_id = ''; form.value.modulos_seleccionados = [];
  if (!id) return;
  const fam = familias.value.find(f => f.id == id);
  if (!fam) return;
  const res = await api.get(`/familias/${encodeURIComponent(fam.nombre)}/ciclos`);
  ciclos.value = res.data;
  // Aplicar ciclo pendiente de autocomplete (viene después de cargar ciclos)
  if (pendingCicloId.value) {
    form.value.ciclo_id = pendingCicloId.value;
    pendingCicloId.value = null;
  }
});

watch(() => form.value.ciclo_id, async (id) => {
  modulos.value = []; form.value.modulos_seleccionados = [];
  if (!id) return;
  const res = await api.get(`/ciclos/${id}/modulos`);
  modulos.value = res.data;
});

watch(() => form.value.empresa_id, (id) => {
  const e = empresas.value.find(x => x.id == id);
  if (!e) return;
  form.value.datos_empresa = {
    nombre: e.nombre_comercial || '', cif: e.cif || '', sector: e.sector || '',
    actividad: e.actividad || '', persona_contacto: e.persona_contacto || '',
    email: e.email_general || '', telefono: e.telefono || '', web: e.web || '', descripcion: '',
  };
  if (e.familias?.length === 1) form.value.familia_id = e.familias[0].id;
});

watch(() => form.value.centro_id, (id) => {
  const c = centros.value.find(x => x.id == id);
  if (!c) return;
  form.value.datos_centro.nombre    = c.nombre    || '';
  form.value.datos_centro.municipio = c.municipio || '';
});

// ── Autocomplete desde microreto ─────────────────────────────────────────────
async function autocompletarDesdeMicroreto(mr) {
  if (!mr) return;
  autocompletando.value = true;

  // Título (solo si vacío)
  if (!form.value.titulo && mr.titulo) form.value.titulo = mr.titulo;

  // Curso
  if (mr.curso) form.value.curso = String(mr.curso) === '1' ? '1º' : '2º';

  // Campos de texto (solo si vacíos)
  if (!form.value.diseno_reto.pregunta_reto && mr.pregunta_reto)
    form.value.diseno_reto.pregunta_reto = mr.pregunta_reto;

  const contexto = [mr.quien_es, mr.dia_a_dia].filter(Boolean).join('\n\n');
  if (!form.value.fundamentacion.contexto && contexto)
    form.value.fundamentacion.contexto = contexto;

  const restricciones = Array.isArray(mr.dificultades) ? mr.dificultades.join(', ') : (mr.dificultades || '');
  if (!form.value.diseno_reto.restricciones && restricciones)
    form.value.diseno_reto.restricciones = restricciones;

  // FK: empresa → dispara watch que rellena datos_empresa y puede setear familia_id
  if (mr.empresa_id) {
    // Reservar el ciclo pendiente ANTES de que la cascada de watches lo resetee
    if (mr.ciclo_id) pendingCicloId.value = mr.ciclo_id;

    form.value.empresa_id = mr.empresa_id;

    // Si el watch de empresa no configuró familia (empresa con varias familias),
    // tomamos la primera del microreto si está disponible
    await nextTick();
    if (!form.value.familia_id && mr.empresa?.familias?.length) {
      form.value.familia_id = mr.empresa.familias[0].id;
    }
  }

  // FK: centro → usar el centro_id de la empresa del microreto (no hay que buscar por nombre)
  if (!form.value.centro_id) {
    const centroId = mr.empresa?.centro_id ?? mr.empresa?.centroEducativo?.id;
    if (centroId) form.value.centro_id = centroId; // el watch rellena datos_centro.nombre y municipio
  }

  setTimeout(() => { autocompletando.value = false; }, 3000);
}

async function cargarProyecto() {
  if (!uuid.value) return;
  cargando.value = true;
  try {
    const res = await api.get(`/startup/proyectos/${uuid.value}`);
    const p   = res.data;
    paso.value = p.paso_actual || 1;
    Object.assign(form.value, {
      titulo: p.titulo || '', empresa_id: p.empresa_id || '',
      centro_id: p.centro_id || '', familia_id: p.familia_id || '',
      ciclo_id: p.ciclo_id || '', curso: p.curso || '',
      microreto_id: p.microreto_id || '', sesion_id: p.sesion_id || null, estado: p.estado || 'borrador',
      ...(p.datos_empresa    && { datos_empresa: p.datos_empresa }),
      ...(p.datos_centro     && { datos_centro: p.datos_centro }),
      ...(p.equipo           && { equipo: p.equipo }),
      ...(p.modulos_seleccionados && { modulos_seleccionados: p.modulos_seleccionados }),
      ...(p.ra_ce            && { ra_ce: p.ra_ce }),
      ...(p.fundamentacion   && { fundamentacion: p.fundamentacion }),
      ...(p.diseno_reto      && { diseno_reto: p.diseno_reto }),
      ...(p.diseno_microproyecto && { diseno_microproyecto: p.diseno_microproyecto }),
      ...(p.resumen          && { resumen: p.resumen }),
      ...(p.objetivos        && { objetivos: p.objetivos }),
      ...(p.kpis             && { kpis: p.kpis }),
    });
  } finally {
    cargando.value = false;
  }
}

onMounted(async () => {
  setTimeout(() => { isLoaded.value = true; }, 80);
  await cargarCatalogos();
  await cargarProyecto();
  // Restaurar o preseleccionar sesión
  const targetSesionId = form.value.sesion_id
  if (targetSesionId) {
    const s = sesiones.value.find(x => x.id === targetSesionId)
    if (s) {
      if (!uuid.value) {
        await seleccionarSesion(s) // creación desde dashboard: autocompletar todo
      } else {
        sesionSeleccionada.value = s // edición: solo restaurar estado visual
      }
    }
  }
});

// ── Guardar ───────────────────────────────────────────────────────────────────
async function guardar(siguientePaso) {
  guardando.value = true; errorMsg.value = '';
  try {
    const payload = { ...form.value, paso_actual: siguientePaso };
    if (uuid.value) {
      await api.put(`/startup/proyectos/${uuid.value}`, payload);
    } else {
      const res = await api.post('/startup/proyectos', {
        titulo:        form.value.titulo,
        microreto_id:  form.value.microreto_id,
        sesion_id:     form.value.sesion_id || null,
      });
      uuid.value = res.data.uuid;
      await api.put(`/startup/proyectos/${uuid.value}`, payload);
      router.replace({ name: 'startup-day-editar', params: { uuid: uuid.value } });
    }
    paso.value = siguientePaso;
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Error al guardar. Inténtalo de nuevo.';
  } finally {
    guardando.value = false;
  }
}

async function publicar() {
  form.value.estado = 'publicado';
  await guardar(paso.value);
  if (!errorMsg.value) router.push({ name: 'startup-day-detalle', params: { uuid: uuid.value } });
}

const progreso = computed(() => Math.round(((paso.value - 1) / (totalPasos - 1)) * 100));
const pasos = [
  { num: 1, label: 'Básicos' }, { num: 2, label: 'Empresa' },
  { num: 3, label: 'Equipo' },  { num: 4, label: 'Currículo' },
  { num: 5, label: 'El Reto' }, { num: 6, label: 'Proyecto' },
  { num: 7, label: 'Objetivos' },{ num: 8, label: 'Publicar' },
];
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] font-sans text-[#1F2937]"
       :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
       style="transition: opacity 0.4s ease, transform 0.4s ease">

    <!-- Fondo decorativo -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[700px] h-[400px]
                bg-[#99CC33] opacity-5 blur-[120px] rounded-full pointer-events-none z-0" />

    <!-- Barra de progreso superior -->
    <div class="sticky top-0 z-20 bg-[#F8FAFC]/95 backdrop-blur border-b border-gray-100 shadow-sm">
      <div class="max-w-3xl mx-auto px-4 py-3">
        <div class="flex items-center gap-4 mb-2">
          <button @click="router.push({ name: 'startup-day' })"
                  class="inline-flex items-center gap-1.5 text-gray-500 hover:text-[#00A859]
                         transition-colors text-xs font-black uppercase tracking-widest shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Salir
          </button>
          <div class="flex-1 min-w-0">
            <p class="text-[9px] font-black uppercase tracking-[0.25em] text-[#00A859]">
              StartUp Day · Paso {{ paso }} de {{ totalPasos }}
            </p>
            <p class="text-xs font-bold text-gray-600 truncate">{{ form.titulo || 'Nuevo microproyecto' }}</p>
          </div>
          <span class="text-xs font-black text-gray-400 shrink-0">{{ progreso }}%</span>
        </div>

        <!-- Barra progreso -->
        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden mb-2">
          <div class="h-full bg-gradient-to-r from-[#00A859] to-[#99CC33] rounded-full transition-all duration-500"
               :style="{ width: progreso + '%' }"/>
        </div>

        <!-- Pasos mini -->
        <div class="flex gap-1 overflow-x-auto scrollbar-none">
          <button v-for="p in pasos" :key="p.num"
                  @click="p.num < paso && (paso = p.num)"
                  :class="[
                    'flex-1 min-w-[52px] py-1 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all',
                    p.num === paso
                      ? 'bg-[#00A859]/10 text-[#00A859] border border-[#00A859]/30'
                      : p.num < paso
                        ? 'bg-gray-100 text-gray-500 hover:text-[#00A859] border border-gray-200 cursor-pointer'
                        : 'bg-transparent text-gray-300 border border-transparent cursor-default'
                  ]">
            {{ p.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Contenido -->
    <div class="relative z-10 max-w-3xl mx-auto px-4 py-8">

      <!-- Error -->
      <div v-if="errorMsg"
           class="mb-4 bg-red-50 border border-red-200 rounded-2xl px-4 py-3 text-sm text-red-600 font-semibold">
        {{ errorMsg }}
      </div>

      <!-- Banner autocomplete -->
      <Transition enter-active-class="transition-all duration-300"
                  enter-from-class="opacity-0 -translate-y-2"
                  leave-active-class="transition-all duration-300"
                  leave-to-class="opacity-0 -translate-y-2">
        <div v-if="autocompletando"
             class="mb-4 flex items-center gap-3 bg-[#00A859]/8 border border-[#00A859]/25
                    rounded-2xl px-4 py-3">
          <svg class="w-4 h-4 text-[#00A859] shrink-0 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <p class="text-sm font-semibold text-[#00A859]">
            Datos autocompletados desde el microreto vinculado. Revisa y ajusta lo que necesites.
          </p>
        </div>
      </Transition>

      <!-- Spinner carga inicial -->
      <div v-if="cargando" class="flex flex-col items-center justify-center py-32">
        <svg class="animate-spin w-10 h-10 text-[#00A859] mb-3" viewBox="0 0 24 24">
          <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
        </svg>
        <p class="text-[#00A859] font-black tracking-widest uppercase text-xs animate-pulse">Cargando...</p>
      </div>

      <template v-else>

        <!-- ═══ PASO 1: Básicos ═══ -->
        <div v-if="paso === 1">
          <div class="mb-6">
            <div class="inline-flex items-center gap-2 mb-2 px-3 py-1 rounded-full bg-[#00A859]/10 border border-[#00A859]/20">
              <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">Paso 1</span>
            </div>
            <h2 class="text-2xl font-black text-[#121212]">Datos básicos</h2>
            <p class="text-gray-500 text-sm mt-1">
              {{ uuid ? 'Revisa los datos de base del microproyecto.' : 'Selecciona la sesión de trabajo — el microproyecto hereda el microreto que contiene.' }}
            </p>
          </div>

          <!-- Sin sesiones (solo en creación) -->
          <div v-if="!uuid && !sesiones.length"
               class="bg-amber-50 border border-amber-200 rounded-[2rem] p-6 flex items-start gap-4">
            <svg class="w-6 h-6 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
              <p class="text-sm font-bold text-amber-800 mb-1">No hay sesiones registradas</p>
              <p class="text-xs text-amber-700 leading-relaxed">
                Para crear un microproyecto es necesario haber registrado primero una sesión con un microreto en el
                <button @click="router.push({ name: 'dashboard-docente' })"
                        class="underline font-black hover:text-amber-900 transition-colors">
                  Dashboard docente
                </button>.
              </p>
            </div>
          </div>

          <template v-else>
            <!-- ── Selector / info de sesión ───────────────────────────────── -->

            <!-- Modo edición: sesión vinculada (read-only) -->
            <div v-if="uuid && sesionSeleccionada"
                 class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 mb-4">
              <p class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-3">Sesión de trabajo vinculada</p>
              <div class="rounded-2xl border border-[#99CC33]/30 bg-[#99CC33]/5 p-4">
                <p class="text-sm font-black text-[#1F2937]">{{ sesionSeleccionada.microreto_titulo }}</p>
                <div class="flex flex-wrap gap-1.5 mt-2">
                  <span class="tag tag-green">{{ formatFecha(sesionSeleccionada.fecha) }}</span>
                  <span v-if="sesionSeleccionada.curso"           class="tag tag-green">{{ sesionSeleccionada.curso }}</span>
                  <span v-if="sesionSeleccionada.grupo"           class="tag tag-lime">Gr. {{ sesionSeleccionada.grupo }}</span>
                  <span v-if="sesionSeleccionada.centro_educativo" class="tag tag-gray">{{ sesionSeleccionada.centro_educativo }}</span>
                </div>
              </div>
            </div>

            <!-- Modo edición sin sesión (proyecto legacy) -->
            <div v-else-if="uuid && !sesionSeleccionada && form.microreto_id"
                 class="bg-amber-50 border border-amber-100 rounded-[2rem] p-5 mb-4 flex items-start gap-3">
              <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <div>
                <p class="text-xs font-bold text-amber-800">Microproyecto sin sesión vinculada</p>
                <p class="text-xs text-amber-600 mt-0.5">Microreto: {{ microretoVinculado?.titulo || '#' + form.microreto_id }}</p>
              </div>
            </div>

            <!-- Modo creación: selector de sesión -->
            <div v-else-if="!uuid"
                 class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-3 mb-4">
              <div class="flex items-start gap-3 pb-3 border-b border-gray-100">
                <div class="w-8 h-8 rounded-xl bg-[#99CC33]/15 border border-[#99CC33]/30
                            flex items-center justify-center shrink-0">
                  <svg class="w-4 h-4 text-[#5a7a00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                             M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                  </svg>
                </div>
                <div>
                  <p class="text-xs font-black text-[#121212]">Sesión de trabajo <span class="text-red-500">*</span></p>
                  <p class="text-[11px] text-gray-400 mt-0.5">El microproyecto hereda el microreto de la sesión y autocompleta empresa, centro y ciclo.</p>
                </div>
              </div>

              <!-- Sesión ya seleccionada -->
              <div v-if="sesionSeleccionada" class="rounded-2xl border border-[#99CC33]/30 bg-[#99CC33]/5 p-4">
                <div class="flex items-start justify-between gap-3">
                  <div class="flex-1 min-w-0">
                    <p class="text-[9px] font-black uppercase tracking-widest text-[#5a7a00] mb-1">Seleccionada</p>
                    <p class="text-sm font-black text-[#1F2937] leading-snug">{{ sesionSeleccionada.microreto_titulo }}</p>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                      <span class="tag tag-green">{{ formatFecha(sesionSeleccionada.fecha) }}</span>
                      <span v-if="sesionSeleccionada.curso"            class="tag tag-green">{{ sesionSeleccionada.curso }}</span>
                      <span v-if="sesionSeleccionada.grupo"            class="tag tag-lime">Gr. {{ sesionSeleccionada.grupo }}</span>
                      <span v-if="sesionSeleccionada.centro_educativo" class="tag tag-gray">{{ sesionSeleccionada.centro_educativo }}</span>
                    </div>
                  </div>
                  <button @click="limpiarSesion"
                          class="shrink-0 px-3 py-1.5 rounded-xl bg-white border border-gray-200
                                 text-[10px] font-black uppercase tracking-widest text-gray-400
                                 hover:border-[#99CC33] hover:text-[#5a7a00] transition-all">
                    Cambiar
                  </button>
                </div>
                <div v-if="microretoVinculado" class="mt-3 pt-3 border-t border-[#99CC33]/20">
                  <p class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-1">Microreto</p>
                  <div class="flex flex-wrap gap-1.5 items-center">
                    <span class="text-xs font-black text-[#1F2937]">{{ microretoVinculado.titulo }}</span>
                    <span v-if="microretoVinculado.familia" class="tag tag-gray">{{ microretoVinculado.familia }}</span>
                    <span v-if="microretoVinculado.ciclo"   class="tag tag-gray">{{ microretoVinculado.ciclo }}</span>
                  </div>
                </div>
              </div>

              <!-- Lista de sesiones para elegir -->
              <div v-else class="space-y-3">
                <div class="relative">
                  <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300 pointer-events-none"
                       fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                  </svg>
                  <input v-model="sesionBusqueda" type="text"
                         placeholder="Buscar por microreto, centro o ciclo..."
                         class="field-input pl-10" />
                </div>
                <p v-if="!sesionesFiltradas.length" class="text-xs text-gray-400 font-medium text-center py-6">
                  Sin resultados.
                </p>
                <ul v-else class="space-y-2 max-h-72 overflow-y-auto pr-1 -mr-1">
                  <li v-for="s in sesionesFiltradas" :key="s.id">
                    <button @click="seleccionarSesion(s)"
                            class="w-full text-left px-4 py-3 rounded-xl border border-gray-100
                                   bg-gray-50 hover:border-[#99CC33]/40 hover:bg-[#99CC33]/5
                                   transition-all group">
                      <p class="text-sm font-black text-[#1F2937] leading-snug
                                group-hover:text-[#5a7a00] transition-colors line-clamp-2">
                        {{ s.microreto_titulo }}
                      </p>
                      <div class="flex flex-wrap gap-1.5 mt-1.5">
                        <span class="tag tag-green">{{ formatFecha(s.fecha) }}</span>
                        <span v-if="s.curso"            class="tag tag-green">{{ s.curso }}</span>
                        <span v-if="s.grupo"            class="tag tag-lime">Gr. {{ s.grupo }}</span>
                        <span v-if="s.centro_educativo" class="tag tag-gray">{{ s.centro_educativo }}</span>
                        <span v-if="s.ciclo_formativo"  class="tag tag-gray">{{ s.ciclo_formativo }}</span>
                      </div>
                    </button>
                  </li>
                </ul>
                <p class="text-[11px] text-red-500 font-medium">Debes seleccionar una sesión para continuar.</p>
              </div>
            </div>

            <!-- Resto de campos básicos -->
            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-5"
                 :class="(!uuid && !sesionSeleccionada) ? 'opacity-50 pointer-events-none select-none' : ''">
              <div>
                <label class="field-label">Título del microproyecto *</label>
                <input v-model="form.titulo" type="text" required class="field-input"
                       placeholder="Ej: Rediseño de packaging sostenible para EcoFab" />
              </div>
              <div class="grid sm:grid-cols-2 gap-4">
                <div>
                  <label class="field-label">Empresa colaboradora</label>
                  <select v-model="form.empresa_id" class="field-input">
                    <option value="">— Seleccionar empresa —</option>
                    <option v-for="e in empresas" :key="e.id" :value="e.id">{{ e.nombre_comercial }}</option>
                  </select>
                </div>
                <div>
                  <label class="field-label">Centro educativo</label>
                  <select v-model="form.centro_id" class="field-input">
                    <option value="">— Seleccionar centro —</option>
                    <option v-for="c in centros" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                  </select>
                </div>
                <div>
                  <label class="field-label">Familia profesional</label>
                  <select v-model="form.familia_id" class="field-input">
                    <option value="">— Seleccionar familia —</option>
                    <option v-for="f in familias" :key="f.id" :value="f.id">{{ f.nombre }}</option>
                  </select>
                </div>
                <div>
                  <label class="field-label">Ciclo formativo</label>
                  <select v-model="form.ciclo_id" class="field-input" :disabled="!ciclos.length">
                    <option value="">— Seleccionar ciclo —</option>
                    <option v-for="c in ciclos" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                  </select>
                </div>
                <div>
                  <label class="field-label">Curso</label>
                  <select v-model="form.curso" class="field-input">
                    <option value="">— Curso —</option>
                    <option>1º</option><option>2º</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="flex justify-end mt-5">
              <button @click="guardar(2)"
                      :disabled="!form.titulo.trim() || (!uuid && !sesionSeleccionada) || !form.microreto_id || guardando"
                      class="btn-primary">
                {{ guardando ? 'Guardando…' : 'Siguiente →' }}
              </button>
            </div>
          </template>
        </div>

        <!-- ═══ PASO 2: Empresa ═══ -->
        <div v-if="paso === 2">
          <div class="mb-6">
            <div class="inline-flex items-center gap-2 mb-2 px-3 py-1 rounded-full bg-[#00A859]/10 border border-[#00A859]/20">
              <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">Paso 2</span>
            </div>
            <h2 class="text-2xl font-black text-[#121212]">Datos de la empresa</h2>
            <p class="text-gray-500 text-sm mt-1">Confirma o completa la información de la empresa colaboradora.</p>
          </div>

          <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
              <div><label class="field-label">Nombre / Razón social</label><input v-model="form.datos_empresa.nombre" type="text" class="field-input" /></div>
              <div><label class="field-label">CIF</label><input v-model="form.datos_empresa.cif" type="text" class="field-input" /></div>
              <div><label class="field-label">Sector</label><input v-model="form.datos_empresa.sector" type="text" class="field-input" /></div>
              <div><label class="field-label">Actividad principal</label><input v-model="form.datos_empresa.actividad" type="text" class="field-input" /></div>
              <div><label class="field-label">Persona de contacto</label><input v-model="form.datos_empresa.persona_contacto" type="text" class="field-input" /></div>
              <div><label class="field-label">Email de contacto</label><input v-model="form.datos_empresa.email" type="email" class="field-input" /></div>
              <div><label class="field-label">Teléfono</label><input v-model="form.datos_empresa.telefono" type="tel" class="field-input" /></div>
              <div><label class="field-label">Web</label><input v-model="form.datos_empresa.web" type="url" placeholder="https://" class="field-input" /></div>
            </div>
            <div>
              <label class="field-label">Descripción breve</label>
              <textarea v-model="form.datos_empresa.descripcion" rows="3" class="field-input resize-none"
                        placeholder="Qué hace la empresa, cuál es su propuesta de valor…" />
            </div>
          </div>

          <div class="flex justify-between mt-5">
            <button @click="paso = 1" class="btn-secondary">← Anterior</button>
            <button @click="guardar(3)" :disabled="guardando" class="btn-primary">{{ guardando ? 'Guardando…' : 'Siguiente →' }}</button>
          </div>
        </div>

        <!-- ═══ PASO 3: Equipo ═══ -->
        <div v-if="paso === 3">
          <div class="mb-6">
            <div class="inline-flex items-center gap-2 mb-2 px-3 py-1 rounded-full bg-[#00A859]/10 border border-[#00A859]/20">
              <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">Paso 3</span>
            </div>
            <h2 class="text-2xl font-black text-[#121212]">Centro y equipo</h2>
            <p class="text-gray-500 text-sm mt-1">Datos del centro educativo y composición del equipo de alumnado.</p>
          </div>

          <div class="space-y-4">
            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-4">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Centro educativo</p>
              <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="field-label">Nombre del centro</label><input v-model="form.datos_centro.nombre" type="text" class="field-input" /></div>
                <div><label class="field-label">Municipio</label><input v-model="form.datos_centro.municipio" type="text" class="field-input" /></div>
                <div><label class="field-label">Docente responsable</label><input v-model="form.datos_centro.docente_nombre" type="text" class="field-input" /></div>
                <div><label class="field-label">Email docente</label><input v-model="form.datos_centro.docente_email" type="email" class="field-input" /></div>
              </div>
            </div>

            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-4">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Equipo de alumnado</p>
              <div class="flex gap-2">
                <input v-model="nuevoAlumno.nombre" type="text" placeholder="Nombre del alumno/a"
                       class="field-input flex-1" @keyup.enter="addAlumno" />
                <input v-model="nuevoAlumno.rol" type="text" placeholder="Rol / función"
                       class="field-input w-36" @keyup.enter="addAlumno" />
                <button @click="addAlumno"
                        class="shrink-0 px-4 py-2.5 bg-[#00A859] text-white rounded-2xl
                               text-sm font-black hover:bg-[#00A859]/90 transition-all active:scale-95">+</button>
              </div>
              <div v-if="form.equipo.alumnos.length" class="flex flex-wrap gap-2">
                <div v-for="(a, i) in form.equipo.alumnos" :key="i"
                     class="flex items-center gap-2 bg-gray-50 border border-gray-200
                            px-3 py-1.5 rounded-full text-sm">
                  <span class="text-[#1F2937] font-semibold">{{ a.nombre }}</span>
                  <span v-if="a.rol" class="text-gray-400 text-xs">· {{ a.rol }}</span>
                  <button @click="removeAlumno(i)" class="text-gray-400 hover:text-red-500 ml-1 font-bold">×</button>
                </div>
              </div>
              <p v-else class="text-xs text-gray-400 italic">Todavía no hay alumnado en el equipo</p>
            </div>
          </div>

          <div class="flex justify-between mt-5">
            <button @click="paso = 2" class="btn-secondary">← Anterior</button>
            <button @click="guardar(4)" :disabled="guardando" class="btn-primary">{{ guardando ? 'Guardando…' : 'Siguiente →' }}</button>
          </div>
        </div>

        <!-- ═══ PASO 4: Módulos y RA/CE ═══ -->
        <div v-if="paso === 4">
          <div class="mb-6">
            <div class="inline-flex items-center gap-2 mb-2 px-3 py-1 rounded-full bg-[#00A859]/10 border border-[#00A859]/20">
              <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">Paso 4</span>
            </div>
            <h2 class="text-2xl font-black text-[#121212]">Módulos y currículum</h2>
            <p class="text-gray-500 text-sm mt-1">Selecciona los módulos del ciclo que se trabajan en este microproyecto.</p>
          </div>

          <div class="space-y-4">
            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-4">Módulos formativos</p>
              <div v-if="!modulos.length" class="text-center py-8 text-gray-400 text-sm">
                {{ form.ciclo_id ? 'Cargando módulos…' : 'Selecciona un ciclo en el paso 1 para ver los módulos' }}
              </div>
              <div v-else class="grid sm:grid-cols-2 gap-2">
                <button v-for="m in modulos" :key="m.id" @click="toggleModulo(m)"
                        :class="[
                          'text-left px-4 py-3 rounded-2xl border text-sm font-semibold transition-all',
                          moduloSeleccionado(m.id)
                            ? 'bg-[#00A859]/10 border-[#00A859]/30 text-[#00A859]'
                            : 'bg-gray-50 border-gray-200 text-gray-600 hover:border-[#00A859]/40 hover:text-[#1F2937]'
                        ]">
                  <span class="text-[10px] text-gray-400 block mb-0.5 font-normal">{{ m.codigoBOE }}</span>
                  {{ m.nombre }}
                </button>
              </div>
            </div>

            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6">
              <label class="field-label">Resultados de Aprendizaje y Criterios de Evaluación</label>
              <textarea v-model="form.ra_ce" rows="4" class="field-input resize-none mt-3"
                        placeholder="Describe los RA y CE que se trabajarán en este microproyecto…" />
            </div>
          </div>

          <div class="flex justify-between mt-5">
            <button @click="paso = 3" class="btn-secondary">← Anterior</button>
            <button @click="guardar(5)" :disabled="guardando" class="btn-primary">{{ guardando ? 'Guardando…' : 'Siguiente →' }}</button>
          </div>
        </div>

        <!-- ═══ PASO 5: El Reto ═══ -->
        <div v-if="paso === 5">
          <div class="mb-6">
            <div class="inline-flex items-center gap-2 mb-2 px-3 py-1 rounded-full bg-[#00A859]/10 border border-[#00A859]/20">
              <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">Paso 5</span>
            </div>
            <h2 class="text-2xl font-black text-[#121212]">El reto</h2>
            <p class="text-gray-500 text-sm mt-1">Define el contexto, la fundamentación y el reto central del microproyecto.</p>
          </div>

          <div class="space-y-4">
            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-4">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Fundamentación</p>
              <div><label class="field-label">Contexto del proyecto</label>
                <textarea v-model="form.fundamentacion.contexto" rows="3" class="field-input resize-none"
                          placeholder="¿Cuál es la situación de partida? ¿Qué problema o necesidad existe?" /></div>
              <div><label class="field-label">Justificación pedagógica</label>
                <textarea v-model="form.fundamentacion.justificacion" rows="3" class="field-input resize-none"
                          placeholder="¿Por qué este reto es relevante para el aprendizaje del alumnado?" /></div>
              <div><label class="field-label">Elemento innovador</label>
                <textarea v-model="form.fundamentacion.innovacion" rows="2" class="field-input resize-none"
                          placeholder="¿Qué tiene de innovador este microproyecto?" /></div>
            </div>

            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-4">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Diseño del reto</p>
              <div><label class="field-label">Descripción del reto *</label>
                <textarea v-model="form.diseno_reto.descripcion" rows="3" class="field-input resize-none" required
                          placeholder="Describe el reto al que se enfrenta el equipo…" /></div>
              <div><label class="field-label">Pregunta reto</label>
                <input v-model="form.diseno_reto.pregunta_reto" type="text" class="field-input"
                       placeholder="¿Cómo podríamos…?" /></div>
              <div><label class="field-label">Restricciones y condicionantes</label>
                <textarea v-model="form.diseno_reto.restricciones" rows="2" class="field-input resize-none"
                          placeholder="Presupuesto, plazos, materiales disponibles…" /></div>
              <div><label class="field-label">Entregables esperados</label>
                <textarea v-model="form.diseno_reto.entregables" rows="2" class="field-input resize-none"
                          placeholder="¿Qué debe entregar el equipo al final del proyecto?" /></div>
            </div>
          </div>

          <div class="flex justify-between mt-5">
            <button @click="paso = 4" class="btn-secondary">← Anterior</button>
            <button @click="guardar(6)" :disabled="guardando" class="btn-primary">{{ guardando ? 'Guardando…' : 'Siguiente →' }}</button>
          </div>
        </div>

        <!-- ═══ PASO 6: Diseño del microproyecto ═══ -->
        <div v-if="paso === 6">
          <div class="mb-6">
            <div class="inline-flex items-center gap-2 mb-2 px-3 py-1 rounded-full bg-[#00A859]/10 border border-[#00A859]/20">
              <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">Paso 6</span>
            </div>
            <h2 class="text-2xl font-black text-[#121212]">Diseño del microproyecto</h2>
            <p class="text-gray-500 text-sm mt-1">Define las fases, metodología y cronograma del trabajo.</p>
          </div>

          <div class="space-y-4">
            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-4">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Fases del proyecto</p>
              <div class="space-y-2">
                <div class="flex gap-2">
                  <input v-model="nuevaFase.nombre" type="text" placeholder="Nombre de la fase"
                         class="field-input flex-1" />
                  <input v-model="nuevaFase.duracion" type="text" placeholder="Duración"
                         class="field-input w-28" />
                  <button @click="addFase"
                          class="shrink-0 px-4 py-2.5 bg-[#00A859] text-white rounded-2xl
                                 text-sm font-black hover:bg-[#00A859]/90 transition-all active:scale-95">+</button>
                </div>
                <input v-model="nuevaFase.descripcion" type="text" placeholder="Descripción breve (opcional)"
                       class="field-input" />
              </div>
              <div v-if="form.diseno_microproyecto.fases.length" class="space-y-2">
                <div v-for="(f, i) in form.diseno_microproyecto.fases" :key="i"
                     class="flex items-start gap-3 bg-gray-50 border border-gray-100 rounded-2xl px-4 py-3">
                  <span class="text-[#00A859] font-black text-sm shrink-0 mt-0.5">{{ i + 1 }}</span>
                  <div class="flex-1 min-w-0">
                    <p class="font-bold text-[#1F2937] text-sm">{{ f.nombre }}
                      <span v-if="f.duracion" class="text-gray-400 font-normal"> · {{ f.duracion }}</span>
                    </p>
                    <p v-if="f.descripcion" class="text-gray-500 text-xs mt-0.5">{{ f.descripcion }}</p>
                  </div>
                  <button @click="removeFase(i)" class="text-gray-400 hover:text-red-500 shrink-0 font-bold">×</button>
                </div>
              </div>
              <p v-else class="text-xs text-gray-400 italic">Todavía no hay fases definidas</p>
            </div>

            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-4">
              <div><label class="field-label">Metodología</label>
                <textarea v-model="form.diseno_microproyecto.metodologia" rows="3" class="field-input resize-none"
                          placeholder="Describe cómo se organizará el trabajo del equipo…" /></div>
              <div><label class="field-label">Cronograma general</label>
                <textarea v-model="form.diseno_microproyecto.cronograma" rows="3" class="field-input resize-none"
                          placeholder="Fechas clave, hitos y plazos…" /></div>
              <div><label class="field-label">Resumen ejecutivo</label>
                <textarea v-model="form.resumen.texto" rows="4" class="field-input resize-none"
                          placeholder="Síntesis del microproyecto para compartir con la empresa…" /></div>
            </div>
          </div>

          <div class="flex justify-between mt-5">
            <button @click="paso = 5" class="btn-secondary">← Anterior</button>
            <button @click="guardar(7)" :disabled="guardando" class="btn-primary">{{ guardando ? 'Guardando…' : 'Siguiente →' }}</button>
          </div>
        </div>

        <!-- ═══ PASO 7: Objetivos y KPIs ═══ -->
        <div v-if="paso === 7">
          <div class="mb-6">
            <div class="inline-flex items-center gap-2 mb-2 px-3 py-1 rounded-full bg-[#00A859]/10 border border-[#00A859]/20">
              <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">Paso 7</span>
            </div>
            <h2 class="text-2xl font-black text-[#121212]">Objetivos y KPIs</h2>
            <p class="text-gray-500 text-sm mt-1">Define los objetivos del proyecto y los indicadores de éxito.</p>
          </div>

          <div class="space-y-4">
            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-3">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Objetivos</p>
              <div class="flex gap-2">
                <input v-model="nuevoObjetivo" type="text" placeholder="Añadir objetivo…"
                       class="field-input flex-1" @keyup.enter="addObjetivo" />
                <button @click="addObjetivo"
                        class="shrink-0 px-4 py-2.5 bg-[#00A859] text-white rounded-2xl
                               text-sm font-black hover:bg-[#00A859]/90 transition-all active:scale-95">+</button>
              </div>
              <ul v-if="form.objetivos.lista.length" class="space-y-2">
                <li v-for="(obj, i) in form.objetivos.lista" :key="i"
                    class="flex items-center gap-2 text-sm text-[#1F2937]">
                  <span class="text-[#00A859] font-black shrink-0">›</span>
                  <span class="flex-1">{{ obj }}</span>
                  <button @click="removeObjetivo(i)" class="text-gray-400 hover:text-red-500 font-bold">×</button>
                </li>
              </ul>
              <p v-else class="text-xs text-gray-400 italic">Añade al menos un objetivo</p>
            </div>

            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 space-y-3">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Indicadores de éxito (KPIs)</p>
              <div class="flex gap-2">
                <input v-model="nuevoKpi" type="text" placeholder="Añadir KPI o indicador…"
                       class="field-input flex-1" @keyup.enter="addKpi" />
                <button @click="addKpi"
                        class="shrink-0 px-4 py-2.5 bg-[#00A859] text-white rounded-2xl
                               text-sm font-black hover:bg-[#00A859]/90 transition-all active:scale-95">+</button>
              </div>
              <ul v-if="form.kpis.lista.length" class="space-y-2">
                <li v-for="(kpi, i) in form.kpis.lista" :key="i"
                    class="flex items-center gap-2 text-sm text-[#1F2937]">
                  <span class="text-[#99CC33] font-black shrink-0">✓</span>
                  <span class="flex-1">{{ kpi }}</span>
                  <button @click="removeKpi(i)" class="text-gray-400 hover:text-red-500 font-bold">×</button>
                </li>
              </ul>
              <p v-else class="text-xs text-gray-400 italic">Los KPIs son opcionales pero recomendados para la validación empresa</p>
            </div>
          </div>

          <div class="flex justify-between mt-5">
            <button @click="paso = 6" class="btn-secondary">← Anterior</button>
            <button @click="guardar(8)" :disabled="guardando" class="btn-primary">{{ guardando ? 'Guardando…' : 'Siguiente →' }}</button>
          </div>
        </div>

        <!-- ═══ PASO 8: Publicar ═══ -->
        <div v-if="paso === 8">
          <div class="mb-6">
            <div class="inline-flex items-center gap-2 mb-2 px-3 py-1 rounded-full bg-[#00A859]/10 border border-[#00A859]/20">
              <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">Paso 8</span>
            </div>
            <h2 class="text-2xl font-black text-[#121212]">Publicar microproyecto</h2>
            <p class="text-gray-500 text-sm mt-1">Al publicar se genera un enlace único para que la empresa valide el proyecto.</p>
          </div>

          <!-- Resumen -->
          <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-6 mb-4">
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-4">Resumen del microproyecto</p>
            <div class="grid sm:grid-cols-2 gap-4 text-sm">
              <div class="p-3 bg-gray-50 rounded-2xl">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1">Título</p>
                <p class="font-bold text-[#1F2937]">{{ form.titulo || '—' }}</p>
              </div>
              <div class="p-3 bg-gray-50 rounded-2xl">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1">Empresa</p>
                <p class="font-bold text-[#1F2937]">{{ form.datos_empresa.nombre || '—' }}</p>
              </div>
              <div class="p-3 bg-gray-50 rounded-2xl">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1">Equipo</p>
                <p class="font-bold text-[#1F2937]">{{ form.equipo.alumnos.length }} alumno/a(s)</p>
              </div>
              <div class="p-3 bg-gray-50 rounded-2xl">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1">Módulos</p>
                <p class="font-bold text-[#1F2937]">{{ form.modulos_seleccionados.length }} seleccionado(s)</p>
              </div>
              <div class="p-3 bg-gray-50 rounded-2xl">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1">Objetivos</p>
                <p class="font-bold text-[#1F2937]">{{ form.objetivos.lista.length }} definido(s)</p>
              </div>
              <div class="p-3 bg-gray-50 rounded-2xl">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider font-bold mb-1">Fases</p>
                <p class="font-bold text-[#1F2937]">{{ form.diseno_microproyecto.fases.length }} definidas</p>
              </div>
            </div>
          </div>

          <div class="bg-[#00A859]/8 border border-[#00A859]/20 rounded-[2rem] p-5 mb-5">
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-[#00A859] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <p class="text-sm text-[#1F2937] leading-relaxed">
                Al publicar se generará un <strong>enlace único</strong> que podrás enviar a la empresa para que valide el microproyecto.
                Podrás seguir editando el proyecto después de publicarlo.
              </p>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row justify-between gap-3">
            <button @click="paso = 7" class="btn-secondary">← Anterior</button>
            <div class="flex gap-3">
              <button @click="guardar(paso)" :disabled="guardando" class="btn-secondary">
                {{ guardando ? 'Guardando…' : 'Guardar borrador' }}
              </button>
              <button @click="publicar" :disabled="guardando || !form.titulo.trim()" class="btn-primary">
                {{ guardando ? 'Publicando…' : 'Publicar microproyecto' }}
              </button>
            </div>
          </div>
        </div>

      </template>
    </div>
  </div>
</template>

<style scoped>
@reference "../style.css";

.field-label {
  @apply block text-[10px] font-black uppercase tracking-[0.15em] text-gray-500 mb-1.5;
}
.field-input {
  @apply w-full bg-white border border-gray-200 rounded-2xl px-4 py-2.5 text-sm
         text-[#1F2937] placeholder-gray-400 focus:outline-none focus:border-[#00A859]
         transition-colors shadow-sm;
}
.btn-primary {
  @apply inline-flex items-center gap-2 px-6 py-2.5 bg-[#00A859] text-white
         rounded-full text-xs font-black uppercase tracking-widest shadow-sm
         hover:bg-[#00A859]/90 hover:shadow-[0_0_0_3px_rgba(0,168,89,0.15)]
         transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed;
}
.btn-secondary {
  @apply inline-flex items-center gap-2 px-6 py-2.5 bg-white border border-gray-200
         rounded-full text-xs font-black uppercase tracking-widest text-[#1F2937]
         shadow-sm hover:border-[#00A859] hover:text-[#00A859]
         transition-all active:scale-95;
}
.tag {
  display: inline-flex; align-items: center;
  padding: 0.125rem 0.5rem; border-radius: 999px;
  font-size: 0.625rem; font-weight: 800;
  text-transform: uppercase; letter-spacing: 0.1em;
}
.tag-gray  { background: #F3F4F6; color: #6B7280; }
.tag-green { background: rgba(0,168,89,0.1); color: #00A859; }
.tag-lime  { background: rgba(153,204,51,0.12); color: #5a7a00; }
</style>
