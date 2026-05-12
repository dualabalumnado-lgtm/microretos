<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api.js';
import { useUIState } from '../composables/useUIState.js';

const route  = useRoute();
const router = useRouter();

const paso       = ref(1);
const totalPasos = 8;
const guardando         = ref(false);
const cargando          = ref(false);
const cargandoProyecto  = ref(false);
const uuid           = ref(route.params.uuid || null);
const isLoaded       = ref(false);
const errorMsg       = ref('');
const publicadoExito = ref(false);

const familias   = ref([]);
const ciclos     = ref([]);
const modulos    = ref([]);
const empresas   = ref([]);
const centros    = ref([]);
const microretos = ref([]);

// Autocomplete desde microreto
const autocompletando        = ref(false);
const pendingCicloId         = ref(null);   // ciclo a seleccionar cuando carguen los ciclos del watch
const cursoAutocompletado    = ref(false);  // para destacar el campo curso
const modulosAutocompletados = ref(false);  // para mostrar callout en paso 4
const raCeAutocompletado     = ref(false);  // para mostrar callout en paso 4
const mrEvalOficial          = ref([]);     // evaluacion_oficial del microreto vinculado

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
  // Usar microreto eager-loaded de la sesión; fallback al array local
  const mr = s.microreto || (s.microreto_id ? microretos.value.find(m => m.id == s.microreto_id) : null)
  if (mr) await autocompletarDesdeMicroreto(mr, s)
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

// ── Estado local de recursos (no se guarda en BD — vive en Cloudinary) ────────
const videosLocales     = ref([])
const documentosLocales = ref([])

// ── Helpers recursos (Cloudinary) ────────────────────────────────────────────
const labelVideo     = ref('')
const labelDocumento = ref('')
const subiendoVideo  = ref(false)
const subiendoDoc    = ref(false)
const errorSubida    = ref('')

// Subida genérica — etiqueta el archivo con el UUID del microproyecto en Cloudinary
async function subirArchivo(tipo, event) {
  const file = event.target.files?.[0]
  if (!file) return

  if (!uuid.value) {
    errorSubida.value = 'Guarda el proyecto al menos una vez antes de adjuntar archivos.'
    return
  }

  const esvideo = tipo === 'video'
  if (esvideo) subiendoVideo.value = true
  else         subiendoDoc.value   = true
  errorSubida.value = ''

  try {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('microproyecto_uuid', uuid.value)
    formData.append('tipo', tipo)
    const etiqueta = esvideo ? labelVideo.value : labelDocumento.value
    if (etiqueta) formData.append('label', etiqueta)

    const res = await api.post('/upload/recurso', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    const entrada = {
      label:         res.data.label || res.data.filename,
      url:           res.data.url,
      public_id:     res.data.public_id,
      resource_type: res.data.resource_type,
      filename:      res.data.filename,
    }
    if (esvideo) {
      videosLocales.value.push(entrada)
      labelVideo.value = ''
    } else {
      documentosLocales.value.push(entrada)
      labelDocumento.value = ''
    }
  } catch (e) {
    errorSubida.value = e.response?.data?.message || 'Error al subir el archivo a Cloudinary.'
  } finally {
    if (esvideo) subiendoVideo.value = false
    else         subiendoDoc.value   = false
    if (event.target) event.target.value = ''
  }
}

async function removeDocumento(i) {
  const doc = documentosLocales.value[i]
  documentosLocales.value.splice(i, 1)
  if (doc?.public_id) {
    try {
      await api.delete('/upload/recurso', {
        data: { public_id: doc.public_id, resource_type: doc.resource_type || 'raw' },
      })
    } catch { /* si falla el borrado remoto, no bloqueamos al usuario */ }
  }
}

async function removeVideo(i) {
  const vid = videosLocales.value[i]
  videosLocales.value.splice(i, 1)
  if (vid?.public_id) {
    try {
      await api.delete('/upload/recurso', {
        data: { public_id: vid.public_id, resource_type: vid.resource_type || 'video' },
      })
    } catch { /* idem */ }
  }
}

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

// ── RA/CE selection ───────────────────────────────────────────────────────────
const modoRaCe         = ref('texto')  // 'manual' | 'ia' | 'texto'
const catalogoRaCe     = ref([])       // [{ modulo, moduloId, ras: [{id,orden,descripcion,criterios:[...]}] }]
const cargandoCatalogo = ref(false)
const cargandoIaRaCe   = ref(false)
const raExpandido      = ref({})       // { raId: bool }
const raChecked        = ref({})       // { raId: bool }
const ceChecked        = ref({})       // { ceId: bool }

async function cargarCatalogoRaCe() {
  const mods = form.value.modulos_seleccionados
  if (!mods.length) { catalogoRaCe.value = []; return }
  cargandoCatalogo.value = true
  try {
    const results = await Promise.all(mods.map(m => api.get(`/modulos/${m.id}/ra-ce`)))
    catalogoRaCe.value = results.map((res, i) => ({
      modulo:   mods[i].nombre,
      moduloId: mods[i].id,
      ras:      res.data.ra || [],
    }))
    raExpandido.value = {}; raChecked.value = {}; ceChecked.value = {}
    catalogoRaCe.value.forEach(mod => {
      mod.ras.forEach(ra => {
        raExpandido.value[ra.id] = true
        raChecked.value[ra.id]   = false
        ra.criterios.forEach(ce => { ceChecked.value[ce.id] = false })
      })
    })
  } finally {
    cargandoCatalogo.value = false
  }
}

function toggleRa(ra) {
  const v = !raChecked.value[ra.id]
  raChecked.value[ra.id] = v
  ra.criterios.forEach(ce => { ceChecked.value[ce.id] = v })
}

function raEstado(ra) {
  const n = ra.criterios.filter(ce => ceChecked.value[ce.id]).length
  if (n === 0) return 'none'
  if (n === ra.criterios.length) return 'all'
  return 'some'
}

function aplicarSeleccionManual() {
  const partes = []
  catalogoRaCe.value.forEach(mod => {
    mod.ras.forEach(ra => {
      const ces = ra.criterios.filter(ce => ceChecked.value[ce.id])
      if (ces.length) {
        const cesStr = ces.map(c => `  • ${c.descripcion}`).join('\n')
        partes.push(`[${mod.modulo}]\nRA: ${ra.descripcion}\nCE:\n${cesStr}`)
      }
    })
  })
  form.value.ra_ce = partes.join('\n\n')
  modoRaCe.value = 'texto'
}

async function sugerirRaCeConIa() {
  cargandoIaRaCe.value = true
  try {
    const res = await api.post('/startup/sugerir-ra-ce', {
      modulo_ids:   form.value.modulos_seleccionados.map(m => m.id),
      titulo:       form.value.titulo,
      pregunta_reto: form.value.diseno_reto.pregunta_reto,
      descripcion:  form.value.diseno_reto.descripcion,
      contexto:     form.value.fundamentacion.contexto,
    })
    if (res.data.ra_ce_texto) {
      form.value.ra_ce = res.data.ra_ce_texto
      modoRaCe.value = 'texto'
    }
  } catch (e) {
    console.error('Error sugiriendo RA/CE con IA', e)
  } finally {
    cargandoIaRaCe.value = false
  }
}

watch(modoRaCe, (modo) => {
  if (modo === 'manual' || modo === 'ia') cargarCatalogoRaCe()
})

const busquedaRaCe = ref('')

const catalogoFiltrado = computed(() => {
  const q = busquedaRaCe.value.trim().toLowerCase()
  if (!q) return catalogoRaCe.value
  return catalogoRaCe.value.map(mod => {
    const ras = mod.ras.map(ra => {
      const raMatch = ra.descripcion.toLowerCase().includes(q)
      const criterios = raMatch
        ? ra.criterios
        : ra.criterios.filter(ce => ce.descripcion.toLowerCase().includes(q))
      return criterios.length ? { ...ra, criterios } : null
    }).filter(Boolean)
    return ras.length ? { ...mod, ras } : null
  }).filter(Boolean)
})

const totalCeSeleccionados = computed(() =>
  Object.values(ceChecked.value).filter(Boolean).length
)
const totalRaSeleccionados = computed(() =>
  catalogoRaCe.value.reduce((acc, mod) =>
    acc + mod.ras.filter(ra => raEstado(ra) !== 'none').length, 0
  )
)

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
  if (cargandoProyecto.value) return;
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
  if (cargandoProyecto.value) return;
  modulos.value = []; form.value.modulos_seleccionados = [];
  if (!id) return;
  const res = await api.get(`/ciclos/${id}/modulos`);
  modulos.value = res.data;
  // Auto-seleccionar módulos que coincidan con los del microreto vinculado
  if (mrEvalOficial.value.length) {
    const nombresEval = mrEvalOficial.value.map(e => e.modulo?.toLowerCase().trim()).filter(Boolean);
    const matches = modulos.value.filter(m =>
      nombresEval.some(n => m.nombre?.toLowerCase().includes(n) || n.includes(m.nombre?.toLowerCase()))
    );
    if (matches.length) {
      form.value.modulos_seleccionados = matches.map(m => ({ id: m.id, nombre: m.nombre }));
      modulosAutocompletados.value = true;
    }
  }
});

watch(() => form.value.empresa_id, (id) => {
  if (cargandoProyecto.value) return;
  const e = empresas.value.find(x => x.id == id);
  if (!e) return;
  form.value.datos_empresa = {
    nombre:            e.nombre_comercial || '',
    cif:               e.cif || '',
    sector:            e.sector || '',
    actividad:         e.actividad || '',
    persona_contacto:  e.persona_contacto || '',
    email:             e.email_contacto || e.email_general || '',
    telefono:          e.telefono || '',
    web:               e.web || '',
    descripcion:       e.dia_a_normal || '',
  };
  if (e.familias?.length === 1) form.value.familia_id = e.familias[0].id;
});

watch(() => form.value.centro_id, (id) => {
  if (cargandoProyecto.value) return;
  const c = centros.value.find(x => x.id == id);
  if (!c) return;
  form.value.datos_centro.nombre    = c.nombre    || '';
  form.value.datos_centro.municipio = c.municipio || '';
});

// ── Autocomplete desde microreto ─────────────────────────────────────────────
async function autocompletarDesdeMicroreto(mr, sesion = null) {
  if (!mr) return;
  autocompletando.value = true;

  // Guardar evaluacion_oficial para auto-seleccionar módulos cuando carguen
  if (Array.isArray(mr.evaluacion_oficial) && mr.evaluacion_oficial.length) {
    mrEvalOficial.value = mr.evaluacion_oficial;
  }

  // ── Paso 1: Básicos ───────────────────────────────────────────────────────
  if (!form.value.titulo && mr.titulo) form.value.titulo = mr.titulo;

  if (mr.curso) {
    form.value.curso = String(mr.curso) === '1' ? '1º' : '2º';
    cursoAutocompletado.value = true;
    setTimeout(() => { cursoAutocompletado.value = false; }, 6000);
  }

  // ── Paso 5: El Reto ───────────────────────────────────────────────────────
  if (!form.value.diseno_reto.pregunta_reto && mr.pregunta_reto)
    form.value.diseno_reto.pregunta_reto = mr.pregunta_reto;

  const contexto = [mr.quien_es, mr.dia_a_dia].filter(Boolean).join('\n\n');
  if (!form.value.fundamentacion.contexto && contexto)
    form.value.fundamentacion.contexto = contexto;

  // Descripción del reto: qué necesita la empresa
  if (!form.value.diseno_reto.descripcion) {
    const queNecesitan = Array.isArray(mr.que_necesitan) ? mr.que_necesitan.join('\n') : (mr.que_necesitan || '');
    const friccion     = [mr.empresa?.friccion_area, mr.empresa?.friccion_problema].filter(Boolean).join('. ');
    const descripcion  = queNecesitan || friccion;
    if (descripcion) form.value.diseno_reto.descripcion = descripcion;
  }

  // Restricciones: dificultades + limitaciones
  if (!form.value.diseno_reto.restricciones) {
    const dificultades = Array.isArray(mr.dificultades) ? mr.dificultades : (mr.dificultades ? [mr.dificultades] : []);
    const limitaciones = Array.isArray(mr.limitaciones) ? mr.limitaciones : (mr.limitaciones ? [mr.limitaciones] : []);
    const restricciones = [...dificultades, ...limitaciones].join(', ');
    if (restricciones) form.value.diseno_reto.restricciones = restricciones;
  }

  // Entregables: prototipos esperados
  if (!form.value.diseno_reto.entregables && Array.isArray(mr.prototipos) && mr.prototipos.length)
    form.value.diseno_reto.entregables = mr.prototipos.join('\n');

  // ── FK: empresa → dispara watch que rellena datos_empresa ─────────────────
  if (mr.empresa_id) {
    // Reservar el ciclo pendiente ANTES de que la cascada de watches lo resetee
    if (mr.ciclo_id) pendingCicloId.value = mr.ciclo_id;

    form.value.empresa_id = mr.empresa_id;

    // Si el watch no configuró familia (empresa con varias), usar la del microreto
    await nextTick();
    if (!form.value.familia_id && mr.empresa?.familias?.length) {
      form.value.familia_id = mr.empresa.familias[0].id;
    }
  }

  // ── FK: centro → empresa.centro_id o búsqueda por nombre desde sesión ────
  if (!form.value.centro_id) {
    const centroId = mr.empresa?.centro_id ?? mr.empresa?.centroEducativo?.id ?? null;
    if (centroId) {
      form.value.centro_id = centroId; // el watch rellena datos_centro.nombre
    } else if (sesion?.centro_educativo) {
      // Intentar coincidencia por nombre en el catálogo de centros
      const q = sesion.centro_educativo.toLowerCase().trim();
      const matched = centros.value.find(c =>
        (c.nombre || '').toLowerCase().includes(q) || q.includes((c.nombre || '').toLowerCase())
      );
      if (matched) {
        form.value.centro_id = matched.id; // el watch rellena datos_centro.nombre
      } else {
        // Fallback directo: escribir el nombre tal cual viene de la sesión
        form.value.datos_centro.nombre = sesion.centro_educativo;
      }
    }
  }

  // ── RA/CE desde evaluacion_oficial ───────────────────────────────────────
  if (!form.value.ra_ce && Array.isArray(mr.evaluacion_oficial) && mr.evaluacion_oficial.length) {
    form.value.ra_ce = mr.evaluacion_oficial.map(e => {
      const ces = Array.isArray(e.ce) ? e.ce.map(c => `  • ${c}`).join('\n') : '';
      return `[${e.modulo}]\nRA: ${e.ra}\nCE:\n${ces}`;
    }).join('\n\n');
    raCeAutocompletado.value = true;
  }

  setTimeout(() => { autocompletando.value = false; }, 3000);
}

async function cargarProyecto() {
  if (!uuid.value) return;
  cargando.value = true;
  cargandoProyecto.value = true;
  try {
    const [proyRes, recRes] = await Promise.all([
      api.get(`/startup/proyectos/${uuid.value}`),
      api.get('/upload/recursos', { params: { microproyecto: uuid.value } }),
    ]);
    const p = proyRes.data;
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
    // Cargar ciclos y módulos en cascada sin que los watchers vacíen los valores
    if (p.familia_id) {
      const fam = familias.value.find(f => f.id == p.familia_id);
      if (fam) {
        const res = await api.get(`/familias/${encodeURIComponent(fam.nombre)}/ciclos`);
        ciclos.value = res.data;
      }
    }
    if (p.ciclo_id) {
      const res = await api.get(`/ciclos/${p.ciclo_id}/modulos`);
      modulos.value = res.data;
    }
    // Recursos viven en Cloudinary — se cargan aparte
    videosLocales.value     = recRes.data.videos    || [];
    documentosLocales.value = recRes.data.documentos || [];
  } finally {
    cargandoProyecto.value = false;
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
  await nextTick();
  modoGuia.value = true;
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
  if (!errorMsg.value) publicadoExito.value = true;
}

const progreso = computed(() => Math.round(((paso.value - 1) / (totalPasos - 1)) * 100));
const pasos = [
  { num: 1, label: 'Básicos' }, { num: 2, label: 'Empresa' },
  { num: 3, label: 'Equipo' },  { num: 4, label: 'Currículo' },
  { num: 5, label: 'El Reto' }, { num: 6, label: 'Proyecto' },
  { num: 7, label: 'Objetivos' },{ num: 8, label: 'Publicar' },
];

// ── Tour guiado ───────────────────────────────────────────────────────────────
const { tourActivo } = useUIState();
const modoGuia = ref(false);

const guiaWizard = [
  {
    titulo: 'Paso 1 · Datos básicos',
    texto: 'Selecciona la sesión de trabajo que origina este proyecto. La sesión proviene del Dashboard Docente y ya tiene un reto asignado, por lo que autocompleta automáticamente empresa, centro y ciclo. Después escribe un título descriptivo y confirma familia, ciclo y curso del grupo de alumnado.',
  },
  {
    titulo: 'Paso 2 · Datos de la empresa',
    texto: 'Completa o corrige la ficha de la empresa colaboradora. Estos datos aparecerán en el dossier del proyecto que verá la empresa. Revisa especialmente el email de contacto, que se usará para enviar el enlace de validación del proyecto.',
  },
  {
    titulo: 'Paso 3 · Centro y equipo',
    texto: 'Rellena los datos del centro educativo (nombre, municipio y docente responsable) y añade a los integrantes del equipo de alumnado. Para cada persona puedes indicar su nombre y el rol dentro del proyecto: diseño, programación, gestión, presentación…',
  },
  {
    titulo: 'Paso 4 · Módulos y currículum',
    texto: 'Selecciona los módulos formativos del ciclo que se trabajan en este proyecto. Si el reto vinculado ya tenía módulos asignados, aparecerán pre-seleccionados. Añade también los RA/CE (Resultados de Aprendizaje y Criterios de Evaluación) más relevantes para justificar el proyecto ante la programación oficial.',
  },
  {
    titulo: 'Paso 5 · El reto',
    texto: 'Define el núcleo del proyecto: la fundamentación (contexto de partida, justificación pedagógica e innovación) y el diseño del reto (descripción de la problemática, pregunta reto en formato "¿Cómo podríamos…?", restricciones que condicionan la solución y los entregables que el equipo debe producir). Cuanto más concreto, más fácil será la evaluación final.',
  },
  {
    titulo: 'Paso 6 · Diseño del proyecto',
    texto: 'Planifica el desarrollo del trabajo: divide el proyecto en fases con nombre y duración estimada, describe la metodología que seguirá el equipo y esboza el cronograma con los hitos clave. Termina con un resumen ejecutivo de 3-4 líneas que la empresa verá al abrir el enlace de validación.',
  },
  {
    titulo: 'Paso 7 · Objetivos y KPIs',
    texto: 'Define los objetivos de aprendizaje del proyecto (qué competencias desarrollará el alumnado) y los indicadores de éxito o KPIs (cómo medirá la empresa que el reto se ha resuelto correctamente). Los KPIs hacen el proyecto evaluable y aumentan el compromiso de la empresa con el resultado final.',
  },
  {
    titulo: 'Paso 8 · Publicar',
    texto: 'Revisa el resumen del proyecto. Aquí también puedes adjuntar vídeos o documentos de presentación que la empresa verá al abrir el enlace de validación. Cuando todo esté listo, pulsa "Publicar" para generar ese enlace único. Si aún falta información, guárdalo como Borrador y termínalo más tarde.',
  },
];

watch(paso, () => { modoGuia.value = true; });
watch(modoGuia, (val) => { tourActivo.value = val; });
onUnmounted(() => { tourActivo.value = false; });
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] font-sans text-[#1F2937]"
       :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
       style="transition: opacity 0.4s ease, transform 0.4s ease">

    <!-- Fondo decorativo -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-175 h-100
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
            <p class="text-xs font-bold text-gray-600 truncate">{{ form.titulo || 'Nuevo proyecto' }}</p>
          </div>
          <span class="text-xs font-black text-gray-400 shrink-0">{{ progreso }}%</span>
          <button @click="modoGuia = true"
                  title="Ver guía de este paso"
                  class="w-7 h-7 rounded-full bg-blue-500/10 border border-blue-500/20 shrink-0
                         flex items-center justify-center text-blue-500 text-xs font-black
                         hover:bg-blue-500/20 transition-all">
            ?
          </button>
        </div>

        <!-- Barra progreso -->
        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden mb-2">
          <div class="h-full bg-linear-to-r from-[#00A859] to-[#99CC33] rounded-full transition-all duration-500"
               :style="{ width: progreso + '%' }"/>
        </div>

        <!-- Pasos mini -->
        <div class="flex gap-1 overflow-x-auto scrollbar-none">
          <button v-for="p in pasos" :key="p.num"
                  @click="p.num < paso && (paso = p.num)"
                  :class="[
                    'flex-1 min-w-13 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all',
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

    <!-- ══ TOUR OVERLAY ══════════════════════════════════════════════════════ -->
    <Transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      leave-active-class="transition-all duration-150 ease-in"
      leave-to-class="opacity-0 scale-95">
      <div v-if="modoGuia" class="fixed inset-0 z-[9990] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/55 pointer-events-auto" />
        <div class="relative pointer-events-auto w-full max-w-md bg-[#1a2332] border border-white/15
                    rounded-3xl shadow-2xl p-7 text-white"
             @click.stop>
          <!-- Cabecera con progreso -->
          <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-2xl bg-[#00A859]/20 flex items-center justify-center
                        text-[#00A859] font-black text-base shrink-0">
              {{ paso }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-[9px] font-black uppercase tracking-widest text-white/40 mb-1.5">
                Guía · Paso {{ paso }} de {{ totalPasos }}
              </p>
              <div class="flex gap-1">
                <span v-for="i in totalPasos" :key="i"
                      class="h-[3px] rounded-full transition-all duration-300"
                      :class="i <= paso ? 'bg-[#00A859] w-5' : 'bg-white/20 w-3'" />
              </div>
            </div>
          </div>
          <!-- Título del paso -->
          <p class="text-[10px] font-black uppercase tracking-widest text-[#00A859] mb-2">
            {{ guiaWizard[paso - 1].titulo }}
          </p>
          <!-- Descripción -->
          <p class="text-[13px] text-white/85 leading-relaxed mb-6">
            {{ guiaWizard[paso - 1].texto }}
          </p>
          <!-- Botones -->
          <div class="flex items-center gap-2">
            <button @click="modoGuia = false"
                    class="flex-1 py-2.5 rounded-xl bg-[#00A859] text-white text-[10px] font-black
                           uppercase tracking-widest hover:bg-[#00A859]/90 transition-all">
              Entendido ✓
            </button>
            <button @click="modoGuia = false"
                    class="px-3 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white/40
                           text-[10px] font-black uppercase tracking-widest hover:text-white/60 transition-all">
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </Transition>

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
            Datos autocompletados desde el reto vinculado. Revisa y ajusta lo que necesites.
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
              {{ uuid ? 'Revisa los datos de base del proyecto.' : 'Selecciona la sesión de trabajo — el proyecto hereda el reto que contiene.' }}
            </p>
          </div>

          <!-- Sin sesiones (solo en creación) -->
          <div v-if="!uuid && !sesiones.length"
               class="bg-amber-50 border border-amber-200 rounded-4xl p-6 flex items-start gap-4">
            <svg class="w-6 h-6 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
              <p class="text-sm font-bold text-amber-800 mb-1">No hay sesiones registradas</p>
              <p class="text-xs text-amber-700 leading-relaxed">
                Para crear un proyecto es necesario haber registrado primero una sesión con un reto en el
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
                 class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6 mb-4">
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
                 class="bg-amber-50 border border-amber-100 rounded-4xl p-5 mb-4 flex items-start gap-3">
              <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <div>
                <p class="text-xs font-bold text-amber-800">Proyecto sin sesión vinculada</p>
                <p class="text-xs text-amber-600 mt-0.5">Reto: {{ microretoVinculado?.titulo || '#' + form.microreto_id }}</p>
              </div>
            </div>

            <!-- Modo creación: selector de sesión -->
            <div v-else-if="!uuid"
                 class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6 space-y-3 mb-4">
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
                  <p class="text-[11px] text-gray-400 mt-0.5">El proyecto hereda el reto de la sesión y autocompleta empresa, centro y ciclo.</p>
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
                  <p class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-1">Reto</p>
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
                         placeholder="Buscar por reto, centro o ciclo..."
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
            <div class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6 space-y-5"
                 :class="(!uuid && !sesionSeleccionada) ? 'opacity-50 pointer-events-none select-none' : ''">
              <div>
                <label class="field-label">Título del proyecto *</label>
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
                <div class="relative">
                  <label class="field-label flex items-center gap-2">
                    Curso
                    <Transition enter-active-class="transition-all duration-300"
                                enter-from-class="opacity-0 scale-75"
                                leave-active-class="transition-all duration-300"
                                leave-to-class="opacity-0 scale-75">
                      <span v-if="cursoAutocompletado"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                                   bg-[#00A859]/15 border border-[#00A859]/30 text-[#00A859]
                                   text-[9px] font-black uppercase tracking-widest">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#00A859] animate-ping" />
                        Del reto
                      </span>
                    </Transition>
                  </label>
                  <select v-model="form.curso"
                          :class="['field-input transition-all duration-500',
                                   cursoAutocompletado ? 'ring-2 ring-[#00A859]/40 border-[#00A859]/50' : '']">
                    <option value="">— Curso —</option>
                    <option>1º</option><option>2º</option>
                  </select>
                  <!-- Toast bocadillo -->
                  <Transition enter-active-class="transition-all duration-300"
                              enter-from-class="opacity-0 translate-y-1"
                              leave-active-class="transition-all duration-300"
                              leave-to-class="opacity-0 translate-y-1">
                    <div v-if="cursoAutocompletado"
                         class="absolute -bottom-8 left-0 z-10 flex items-center gap-1.5
                                bg-[#00A859] text-white px-3 py-1 rounded-full shadow-md
                                text-[10px] font-bold whitespace-nowrap pointer-events-none">
                      <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                      </svg>
                      Rellenado automáticamente desde el reto
                    </div>
                  </Transition>
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

          <div class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6 space-y-4">
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
            <div class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6 space-y-4">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Centro educativo</p>
              <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="field-label">Nombre del centro</label><input v-model="form.datos_centro.nombre" type="text" class="field-input" /></div>
                <div><label class="field-label">Municipio</label><input v-model="form.datos_centro.municipio" type="text" class="field-input" /></div>
                <div><label class="field-label">Docente responsable</label><input v-model="form.datos_centro.docente_nombre" type="text" class="field-input" /></div>
                <div><label class="field-label">Email docente</label><input v-model="form.datos_centro.docente_email" type="email" class="field-input" /></div>
              </div>
            </div>

            <div class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6 space-y-4">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Equipo de alumnado</p>
              <div class="flex gap-2">
                <input v-model="nuevoAlumno.nombre" type="text" placeholder="Nombre o identificador del alumno/a"
                       class="field-input flex-1" @keyup.enter="addAlumno" />
                <input v-model="nuevoAlumno.rol" type="text" placeholder="Rol / función"
                       class="field-input !w-36" @keyup.enter="addAlumno" />
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
            <p class="text-gray-500 text-sm mt-1">Selecciona los módulos del ciclo que se trabajan en este proyecto.</p>
          </div>

          <div class="space-y-4">

            <!-- Callout módulos autocompletados -->
            <Transition enter-active-class="transition-all duration-300"
                        enter-from-class="opacity-0 -translate-y-2"
                        leave-active-class="transition-all duration-200"
                        leave-to-class="opacity-0 -translate-y-2">
              <div v-if="modulosAutocompletados"
                   class="flex items-start gap-3 bg-[#00A859]/8 border border-[#00A859]/25
                          rounded-2xl px-4 py-3">
                <svg class="w-4 h-4 text-[#00A859] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-black text-[#00A859] mb-0.5">Módulos seleccionados automáticamente</p>
                  <p class="text-xs text-[#00A859]/70 leading-relaxed">
                    Esto se basa en el reto vinculado. Si crees que se trabajan otros módulos,
                    revisa el reto para que esté acorde, o ajusta la selección manualmente.
                  </p>
                </div>
                <button @click="modulosAutocompletados = false"
                        class="shrink-0 text-[#00A859]/40 hover:text-[#00A859] transition-colors mt-0.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </Transition>

            <div class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6">
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

            <div class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6">
              <!-- Cabecera -->
              <div class="flex items-start justify-between gap-3 mb-4">
                <label class="field-label">Resultados de Aprendizaje y Criterios de Evaluación</label>
                <Transition enter-active-class="transition-all duration-300"
                            enter-from-class="opacity-0 scale-75"
                            leave-active-class="transition-all duration-200"
                            leave-to-class="opacity-0 scale-75">
                  <span v-if="raCeAutocompletado"
                        class="shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-full
                               bg-amber-50 border border-amber-200 text-amber-600
                               text-[9px] font-black uppercase tracking-widest">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400" />
                    Del reto
                  </span>
                </Transition>
              </div>

              <!-- Callout autocompletado -->
              <Transition enter-active-class="transition-all duration-300"
                          enter-from-class="opacity-0 -translate-y-2"
                          leave-active-class="transition-all duration-200"
                          leave-to-class="opacity-0 -translate-y-2">
                <div v-if="raCeAutocompletado"
                     class="flex items-start gap-3 bg-amber-50 border border-amber-200
                            rounded-2xl px-4 py-3 mb-4">
                  <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <p class="text-xs text-amber-700 leading-relaxed flex-1">
                    RA y CE generados a partir del reto vinculado. Revisa que estén alineados con tu
                    programación o ajusta la selección.
                  </p>
                  <button @click="raCeAutocompletado = false"
                          class="shrink-0 text-amber-400 hover:text-amber-600 transition-colors mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </div>
              </Transition>

              <!-- Selector de modo -->
              <div class="flex gap-2 mb-5 p-1 bg-gray-100 rounded-2xl">
                <button v-for="modo in [
                    { key: 'manual', label: 'Selección manual', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' },
                    { key: 'ia',     label: 'Sugerir con IA',   icon: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z' },
                    { key: 'texto',  label: 'Texto libre',      icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
                  ]" :key="modo.key" @click="modoRaCe = modo.key"
                  :class="[
                    'flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition-all',
                    modoRaCe === modo.key
                      ? 'bg-white shadow text-[#00A859]'
                      : 'text-gray-500 hover:text-gray-700'
                  ]">
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="modo.icon"/>
                  </svg>
                  <span class="hidden sm:inline">{{ modo.label }}</span>
                </button>
              </div>

              <!-- ── MODO MANUAL ── -->
              <div v-if="modoRaCe === 'manual'">

                <!-- Cargando -->
                <div v-if="cargandoCatalogo" class="flex flex-col items-center justify-center py-12 gap-3">
                  <div class="w-8 h-8 rounded-full border-4 border-[#00A859]/20 border-t-[#00A859] animate-spin" />
                  <p class="text-sm text-gray-400">Cargando catálogo…</p>
                </div>

                <!-- Sin módulos seleccionados -->
                <div v-else-if="!catalogoRaCe.length"
                     class="py-8 text-center text-sm text-gray-400 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                  <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                  </svg>
                  Selecciona módulos en la sección superior para ver su catálogo de RA y CE.
                </div>

                <div v-else>
                  <!-- Buscador -->
                  <div class="relative mb-4">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input v-model="busquedaRaCe" type="text"
                           placeholder="Buscar resultado de aprendizaje o criterio de evaluación…"
                           class="w-full pl-9 pr-8 py-2.5 text-sm bg-gray-50 border border-gray-200
                                  rounded-2xl focus:outline-none focus:border-[#00A859]/50 focus:bg-white
                                  transition-colors placeholder-gray-400" />
                    <button v-if="busquedaRaCe" @click="busquedaRaCe = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>

                  <!-- Barra de contadores -->
                  <div class="flex flex-wrap items-center gap-3 px-4 py-2 mb-3 bg-indigo-50/60 border border-indigo-100 rounded-2xl">
                    <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest mr-1">Selección:</span>
                    <div class="flex items-center gap-1.5">
                      <span class="w-1.5 h-1.5 rounded-full bg-[#00A859] shrink-0" />
                      <span class="text-[11px] font-black text-[#00A859]">{{ totalRaSeleccionados }}</span>
                      <span class="text-[11px] text-[#00A859]/70">RA</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                      <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0" />
                      <span class="text-[11px] font-black text-amber-600">{{ totalCeSeleccionados }}</span>
                      <span class="text-[11px] text-amber-500/70">CE</span>
                    </div>
                  </div>

                  <!-- Sin resultados de búsqueda -->
                  <div v-if="!catalogoFiltrado.length"
                       class="py-6 text-center text-sm text-gray-400 bg-gray-50 rounded-2xl">
                    Sin resultados para "<span class="font-semibold">{{ busquedaRaCe }}</span>"
                  </div>

                  <!-- Lista de módulos al estilo CatalogoBoeModal -->
                  <div v-else class="space-y-2">
                    <div v-for="mod in catalogoFiltrado" :key="mod.moduloId"
                         class="rounded-2xl border border-gray-100 overflow-hidden">

                      <!-- Cabecera módulo -->
                      <div class="flex items-center gap-2 px-4 py-3 bg-gray-50/80 border-b border-gray-100">
                        <span class="text-[10px] font-black uppercase tracking-widest
                                     bg-indigo-100/60 text-indigo-500 px-2 py-0.5 rounded-full shrink-0">MF</span>
                        <span class="flex-1 text-xs font-bold text-gray-700">{{ mod.modulo }}</span>
                        <span class="text-[10px] text-gray-400">{{ mod.ras.length }} RA</span>
                      </div>

                      <!-- Resultados de Aprendizaje -->
                      <div class="divide-y divide-gray-50">
                        <div v-for="ra in mod.ras" :key="ra.id"
                             class="overflow-hidden"
                             :class="raEstado(ra) !== 'none' ? 'bg-[#00A859]/4' : ''">

                          <!-- Fila RA -->
                          <div class="flex items-start gap-2.5 px-4 py-3">
                            <!-- Checkbox RA -->
                            <button @click="toggleRa(ra)"
                                    class="mt-0.5 shrink-0 w-4 h-4 rounded border-2 flex items-center justify-center transition-all"
                                    :class="raEstado(ra) === 'all'
                                      ? 'bg-[#00A859] border-[#00A859]'
                                      : raEstado(ra) === 'some'
                                        ? 'bg-[#00A859]/30 border-[#00A859]'
                                        : 'bg-white border-gray-300 hover:border-[#00A859]/50'">
                              <svg v-if="raEstado(ra) !== 'none'" class="w-2.5 h-2.5 text-white"
                                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="raEstado(ra) === 'all'" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 12h14"/>
                              </svg>
                            </button>
                            <!-- Badge RA + texto (igual que CatalogoBoeModal) -->
                            <div class="flex items-center gap-1.5 shrink-0 mt-0.5">
                              <span class="text-[9px] font-black uppercase tracking-widest text-[#00A859]
                                           bg-[#00A859]/10 px-2 py-0.5 rounded-full">RA{{ ra.orden }}</span>
                              <span class="text-[9px] text-gray-300">#{{ ra.id }}</span>
                            </div>
                            <p class="flex-1 text-[11px] font-semibold text-gray-700 leading-snug">{{ ra.descripcion }}</p>
                            <!-- Expand toggle -->
                            <button @click="raExpandido[ra.id] = !raExpandido[ra.id]"
                                    class="shrink-0 text-gray-400 hover:text-gray-600 transition-colors mt-0.5 ml-1">
                              <svg class="w-3.5 h-3.5 transition-transform duration-200"
                                   :class="raExpandido[ra.id] ? 'rotate-180' : ''"
                                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                              </svg>
                            </button>
                          </div>

                          <!-- Criterios de Evaluación (igual que CatalogoBoeModal) -->
                          <div v-if="raExpandido[ra.id]"
                               class="border-t border-[#00A859]/15 px-4 pb-3 pt-2.5 bg-white/60">
                            <div class="flex items-center gap-1.5 mb-2">
                              <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0" />
                              <span class="text-[9px] font-black uppercase tracking-widest text-amber-600">
                                Criterios de Evaluación
                              </span>
                            </div>
                            <div class="space-y-1.5 pl-2">
                              <label v-for="ce in ra.criterios" :key="ce.id"
                                     class="flex items-start gap-2.5 cursor-pointer group">
                                <div class="mt-0.5 shrink-0 w-3.5 h-3.5 rounded border-2 flex items-center justify-center transition-all"
                                     :class="ceChecked[ce.id]
                                       ? 'bg-amber-400 border-amber-400'
                                       : 'bg-white border-gray-300 group-hover:border-amber-300'">
                                  <svg v-if="ceChecked[ce.id]" class="w-2 h-2 text-white"
                                       fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                  </svg>
                                </div>
                                <input type="checkbox" v-model="ceChecked[ce.id]" class="sr-only" />
                                <span class="text-[10px] text-gray-600 leading-snug">
                                  <span class="font-bold text-amber-500 mr-1">{{ ce.orden }}.</span>{{ ce.descripcion }}
                                </span>
                              </label>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Pie: botón aplicar -->
                  <div class="mt-4 flex items-center justify-between gap-3">
                    <p class="text-xs text-gray-400">
                      <template v-if="totalCeSeleccionados > 0">
                        {{ totalCeSeleccionados }} CE de {{ totalRaSeleccionados }} RA seleccionados
                      </template>
                      <template v-else>
                        Marca los CE que se trabajarán en el proyecto.
                      </template>
                    </p>
                    <button @click="aplicarSeleccionManual"
                            :disabled="totalCeSeleccionados === 0"
                            class="px-5 py-3 bg-[#00A859] text-white rounded-2xl text-sm font-black tracking-wide
                                   hover:bg-[#00A859]/90 disabled:opacity-40 transition-colors shrink-0">
                      Aplicar selección →
                    </button>
                  </div>
                </div>
              </div>

              <!-- ── MODO IA ── -->
              <div v-else-if="modoRaCe === 'ia'" class="space-y-4">
                <div class="bg-violet-50 border border-violet-200 rounded-2xl px-4 py-3 text-sm text-violet-700 leading-relaxed">
                  La IA analizará el contexto del proyecto y los módulos seleccionados para sugerir los RA y CE más relevantes del catálogo oficial.
                </div>
                <button @click="sugerirRaCeConIa"
                        :disabled="cargandoIaRaCe || !form.modulos_seleccionados.length"
                        class="w-full py-4 rounded-2xl text-sm font-black tracking-wide transition-all flex items-center justify-center gap-2
                               bg-violet-600 text-white hover:bg-violet-700 disabled:opacity-40">
                  <template v-if="cargandoIaRaCe">
                    <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24">
                      <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
                    </svg>
                    Analizando currículum…
                  </template>
                  <template v-else>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    Sugerir RA y CE con IA
                  </template>
                </button>
                <p v-if="!form.modulos_seleccionados.length" class="text-xs text-gray-400 text-center">
                  Selecciona al menos un módulo para usar esta opción.
                </p>
              </div>

              <!-- ── MODO TEXTO LIBRE ── -->
              <div v-else>
                <textarea v-model="form.ra_ce" rows="6" class="field-input resize-none"
                          placeholder="Describe los RA y CE que se trabajarán en este proyecto…" />
              </div>
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
            <p class="text-gray-500 text-sm mt-1">Define el contexto, la fundamentación y el reto central del proyecto.</p>
          </div>

          <div class="space-y-4">
            <div class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6 space-y-4">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Fundamentación</p>
              <div><label class="field-label">Contexto del proyecto</label>
                <textarea v-model="form.fundamentacion.contexto" rows="3" class="field-input resize-none"
                          placeholder="¿Cuál es la situación de partida? ¿Qué problema o necesidad existe?" /></div>
              <div><label class="field-label">Justificación pedagógica</label>
                <textarea v-model="form.fundamentacion.justificacion" rows="3" class="field-input resize-none"
                          placeholder="¿Por qué este reto es relevante para el aprendizaje del alumnado?" /></div>
              <div><label class="field-label">Elemento innovador</label>
                <textarea v-model="form.fundamentacion.innovacion" rows="2" class="field-input resize-none"
                          placeholder="¿Qué tiene de innovador este proyecto?" /></div>
            </div>

            <div class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6 space-y-4">
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

        <!-- ═══ PASO 6: Diseño del proyecto ═══ -->
        <div v-if="paso === 6">
          <div class="mb-6">
            <div class="inline-flex items-center gap-2 mb-2 px-3 py-1 rounded-full bg-[#00A859]/10 border border-[#00A859]/20">
              <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">Paso 6</span>
            </div>
            <h2 class="text-2xl font-black text-[#121212]">Diseño del proyecto</h2>
            <p class="text-gray-500 text-sm mt-1">Define las fases, metodología y cronograma del trabajo.</p>
          </div>

          <div class="space-y-4">
            <div class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6 space-y-4">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Fases del proyecto</p>
              <div class="space-y-2">
                <div class="flex gap-2">
                  <input v-model="nuevaFase.nombre" type="text" placeholder="Nombre de la fase"
                         class="field-input flex-1" />
                  <input v-model="nuevaFase.duracion" type="text" placeholder="Duración"
                         class="field-input !w-28" />
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

            <div class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6 space-y-4">
              <div><label class="field-label">Metodología</label>
                <textarea v-model="form.diseno_microproyecto.metodologia" rows="3" class="field-input resize-none"
                          placeholder="Describe cómo se organizará el trabajo del equipo…" /></div>
              <div><label class="field-label">Cronograma general</label>
                <textarea v-model="form.diseno_microproyecto.cronograma" rows="3" class="field-input resize-none"
                          placeholder="Fechas clave, hitos y plazos…" /></div>
              <div><label class="field-label">Resumen ejecutivo</label>
                <textarea v-model="form.resumen.texto" rows="4" class="field-input resize-none"
                          placeholder="Síntesis del proyecto para compartir con la empresa…" /></div>
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
            <div class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6 space-y-3">
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

            <div class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6 space-y-3">
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

          <!-- ── Estado de éxito tras publicar ── -->
          <div v-if="publicadoExito" class="flex flex-col items-center text-center py-8 gap-6">
            <div class="w-20 h-20 rounded-full bg-[#00A859]/10 border-2 border-[#00A859]/20 flex items-center justify-center">
              <svg class="w-10 h-10 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            <div>
              <h2 class="text-2xl font-black text-[#121212]">¡Proyecto publicado!</h2>
              <p class="text-gray-500 text-sm mt-1.5 max-w-sm mx-auto">
                El enlace de validación ya está listo. Puedes enviárselo a la empresa por correo para que valide el proyecto.
              </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full max-w-sm">
              <button v-if="form.empresa_id"
                      @click="router.push({ name: 'empresas', query: { empresa_id: form.empresa_id, proyecto_uuid: uuid, panel: 'validacion' } })"
                      class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 bg-amber-500 text-white
                             rounded-full text-xs font-black uppercase tracking-widest shadow-sm
                             hover:bg-amber-400 transition-all active:scale-95">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Enviar enlace a empresa
              </button>
              <button @click="router.push({ name: 'startup-day-detalle', params: { uuid } })"
                      class="flex-1 btn-secondary justify-center">
                Ver proyecto
              </button>
            </div>
          </div>

          <template v-else>
          <div class="mb-6">
            <div class="inline-flex items-center gap-2 mb-2 px-3 py-1 rounded-full bg-[#00A859]/10 border border-[#00A859]/20">
              <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">Paso 8</span>
            </div>
            <h2 class="text-2xl font-black text-[#121212]">Publicar proyecto</h2>
            <p class="text-gray-500 text-sm mt-1">Al publicar se genera un enlace único para que la empresa valide el proyecto.</p>
          </div>

          <!-- Resumen -->
          <div class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6 mb-4">
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 mb-4">Resumen del proyecto</p>
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

          <!-- Recursos: vídeos y documentos para la empresa (almacenados en Cloudinary) -->
          <div class="bg-white rounded-4xl border border-gray-100 shadow-sm p-6 mb-4">
            <div class="flex items-center gap-3 mb-1">
              <div class="w-8 h-8 rounded-xl bg-[#00A859]/10 border border-[#00A859]/20
                          flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
              </div>
              <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#00A859]">Recursos para la empresa</p>
                <p class="text-xs text-gray-400 mt-0.5">
                  Los archivos se suben a <strong>Cloudinary</strong>. La empresa los verá al abrir el enlace de validación.
                </p>
              </div>
            </div>

            <!-- Error de subida global -->
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 -translate-y-1">
              <p v-if="errorSubida" class="mt-3 text-xs text-red-500 font-medium">{{ errorSubida }}</p>
            </Transition>

            <div class="mt-5 space-y-5">

              <!-- ── VÍDEOS ─────────────────────────────────────────── -->
              <div>
                <p class="text-[10px] font-black uppercase tracking-wider text-gray-500 mb-3">Vídeos</p>

                <!-- Lista -->
                <div v-if="videosLocales.length" class="space-y-2 mb-3">
                  <div v-for="(v, i) in videosLocales" :key="i"
                       class="flex items-center gap-2 p-2.5 bg-gray-50 rounded-xl border border-gray-100">
                    <div class="w-7 h-7 rounded-lg bg-blue-50 shrink-0 flex items-center justify-center">
                      <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/>
                      </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-xs font-bold text-gray-700 truncate">{{ v.label || v.filename }}</p>
                      <p class="text-[9px] text-blue-400/80 truncate">Cloudinary · {{ v.filename }}</p>
                    </div>
                    <button @click="removeVideo(i)"
                            class="w-6 h-6 rounded-lg bg-red-50 flex items-center justify-center text-red-400 hover:bg-red-100 transition-colors shrink-0">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                </div>

                <!-- Uploader -->
                <div class="flex gap-2 items-center">
                  <input v-model="labelVideo" type="text" placeholder="Nombre del vídeo (opcional)"
                         class="w-44 shrink-0 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs
                                text-gray-700 placeholder-gray-300 focus:outline-none focus:border-[#00A859] transition-colors"/>
                  <label class="flex-1 flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl
                                border-2 border-dashed border-gray-200 bg-gray-50
                                cursor-pointer hover:border-[#00A859]/40 hover:bg-[#00A859]/5 transition-all"
                         :class="subiendoVideo ? 'opacity-50 pointer-events-none' : ''">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <span class="text-xs text-gray-400 font-medium">
                      {{ subiendoVideo ? 'Subiendo vídeo...' : 'Seleccionar vídeo (MP4, MOV, AVI, WebM...)' }}
                    </span>
                    <input type="file" accept="video/*"
                           class="sr-only" @change="subirArchivo('video', $event)" :disabled="subiendoVideo"/>
                  </label>
                </div>
              </div>

              <!-- ── DOCUMENTOS ─────────────────────────────────────── -->
              <div>
                <p class="text-[10px] font-black uppercase tracking-wider text-gray-500 mb-3">Documentos</p>

                <!-- Lista -->
                <div v-if="documentosLocales.length" class="space-y-2 mb-3">
                  <div v-for="(d, i) in documentosLocales" :key="i"
                       class="flex items-center gap-2 p-2.5 bg-gray-50 rounded-xl border border-gray-100">
                    <div class="w-7 h-7 rounded-lg bg-[#00A859]/10 shrink-0 flex items-center justify-center">
                      <svg class="w-3.5 h-3.5 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                      </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="text-xs font-bold text-gray-700 truncate">{{ d.label || d.filename }}</p>
                      <p class="text-[9px] text-blue-400/80 truncate">Cloudinary · {{ d.filename }}</p>
                    </div>
                    <button @click="removeDocumento(i)"
                            class="w-6 h-6 rounded-lg bg-red-50 flex items-center justify-center text-red-400 hover:bg-red-100 transition-colors shrink-0">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                </div>

                <!-- Uploader -->
                <div class="flex gap-2 items-center">
                  <input v-model="labelDocumento" type="text" placeholder="Nombre del documento (opcional)"
                         class="w-44 shrink-0 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs
                                text-gray-700 placeholder-gray-300 focus:outline-none focus:border-[#00A859] transition-colors"/>
                  <label class="flex-1 flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl
                                border-2 border-dashed border-gray-200 bg-gray-50
                                cursor-pointer hover:border-[#00A859]/40 hover:bg-[#00A859]/5 transition-all"
                         :class="subiendoDoc ? 'opacity-50 pointer-events-none' : ''">
                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <span class="text-xs text-gray-400 font-medium">
                      {{ subiendoDoc ? 'Subiendo documento...' : 'Seleccionar archivo (PDF, Word, Excel, imagen...)' }}
                    </span>
                    <input type="file"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.png,.jpg,.jpeg,.gif,.webp,.zip"
                           class="sr-only" @change="subirArchivo('documento', $event)" :disabled="subiendoDoc"/>
                  </label>
                </div>
                <p class="text-[9px] text-gray-300 mt-1.5 pl-1">Máx. 20 MB por archivo · Los archivos se alojan en Cloudinary. La BD no almacena ningún fichero.</p>
              </div>

            </div>
          </div>

          <div class="bg-[#00A859]/8 border border-[#00A859]/20 rounded-4xl p-5 mb-5">
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-[#00A859] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <p class="text-sm text-[#1F2937] leading-relaxed">
                Al publicar se generará un <strong>enlace único</strong> que podrás enviar a la empresa para que valide el proyecto.
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
                {{ guardando ? 'Publicando…' : 'Publicar proyecto' }}
              </button>
            </div>
          </div>
          </template>
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
