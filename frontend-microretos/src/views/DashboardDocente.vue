<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRoute, useRouter, onBeforeRouteUpdate } from 'vue-router'
import api from '../api.js'
import MicroretoModal from '../components/MicroretoModal.vue'
import BienvenidaModal from '../components/BienvenidaModal_DashboardDocente.vue'
import { useUIState } from '../composables/useUIState.js'

const { tourActivo } = useUIState()

const route  = useRoute()
const router = useRouter()

// ─── Estado del microreto seleccionado ────────────────────────────────────────
const microretoSeleccionado = ref(null)
const cargandoMicroreto     = ref(false)

// ─── Formulario de sesión ──────────────────────────────────────────────────────
const form = ref({
  microreto_id:     '',
  microreto_titulo: '',
  fecha:            new Date().toISOString().slice(0, 10),
  centro_educativo: '',
  ciclo_formativo:  '',
  curso:            '',
  grupo:            '',
  num_alumnos:      '',
  notas:            '',
})

const guardando  = ref(false)
const guardadoOk = ref(false)

// ─── Historial de sesiones (API) ─────────────────────────────────────────────
const sesiones         = ref([])
const cargandoSesiones = ref(false)

async function cargarSesiones() {
  cargandoSesiones.value = true
  try {
    const res = await api.get('/sesiones')
    sesiones.value = res.data
  } catch (e) {
    console.error('Error cargando sesiones:', e)
  } finally {
    cargandoSesiones.value = false
  }
}

// ─── Buscador / selector de microretos ───────────────────────────────────────
const mostrarBuscador    = ref(false)
const todosMicroretos    = ref([])      // todos los microretos de la API
const todosCentros       = ref([])      // todos los centros de la API
const cargandoCatalogo   = ref(false)
const catalogoCargado    = ref(false)

// Filtros en cascada
const filtroCentro  = ref('')
const filtroFamilia = ref('')
const filtroCiclo   = ref('')
const filtroCurso   = ref('')
const busqueda       = ref('')
const filtroSimulado = ref('') // '' | 'si' | 'no'

// ── Derivados para los selects ────────────────────────────────────────────────

const centrosDisponibles = computed(() => todosCentros.value.map(c => c.nombre).sort())

// Familias filtradas por centro seleccionado
const familiasDisponibles = computed(() => {
  if (filtroCentro.value) {
    const centro = todosCentros.value.find(c => c.nombre === filtroCentro.value)
    if (centro) {
      const set = new Set(centro.ciclos.map(c => c.familia_nombre).filter(Boolean))
      return [...set].sort()
    }
  }
  const set = new Set(todosMicroretos.value.map(m => m.familia).filter(Boolean))
  return [...set].sort()
})

// Ciclos filtrados por centro + familia
const ciclosDisponibles = computed(() => {
  if (filtroCentro.value) {
    const centro = todosCentros.value.find(c => c.nombre === filtroCentro.value)
    if (centro) {
      let ciclos = centro.ciclos
      if (filtroFamilia.value)
        ciclos = ciclos.filter(c => c.familia_nombre === filtroFamilia.value)
      const set = new Set(ciclos.map(c => c.nombre).filter(Boolean))
      return [...set].sort()
    }
  }
  let base = todosMicroretos.value
  if (filtroFamilia.value)
    base = base.filter(m => m.familia === filtroFamilia.value)
  const set = new Set(base.map(m => m.ciclo).filter(Boolean))
  return [...set].sort()
})

// Cursos filtrados por centro + familia + ciclo
const cursosDisponibles = computed(() => {
  let base = todosMicroretos.value
  if (filtroCentro.value)
    base = base.filter(m => (m.centro_educativo || m.centro) === filtroCentro.value)
  if (filtroFamilia.value)
    base = base.filter(m => m.familia === filtroFamilia.value)
  if (filtroCiclo.value)
    base = base.filter(m => m.ciclo === filtroCiclo.value)
  const set = new Set(
    base.map(m => {
      if (!m.curso) return null
      const n = parseInt(m.curso)
      return n === 1 ? '1º' : n === 2 ? '2º' : String(m.curso)
    }).filter(Boolean)
  )
  return [...set].sort()
})

// Resultados finales
const resultadosFiltrados = computed(() => {
  let base = todosMicroretos.value

  if (filtroCentro.value)
    base = base.filter(m => (m.centro_educativo || m.centro) === filtroCentro.value)
  if (filtroFamilia.value)
    base = base.filter(m => m.familia === filtroFamilia.value)
  if (filtroCiclo.value)
    base = base.filter(m => m.ciclo === filtroCiclo.value)
  if (filtroCurso.value) {
    const num = filtroCurso.value.replace('º', '')
    base = base.filter(m => m.curso != null && String(m.curso) === num)
  }
  if (busqueda.value.trim()) {
    const q = busqueda.value.trim().toLowerCase()
    base = base.filter(m =>
      (m.titulo    || '').toLowerCase().includes(q) ||
      (m.ciclo     || '').toLowerCase().includes(q) ||
      (m.familia   || '').toLowerCase().includes(q) ||
      (m.curso != null && String(m.curso).includes(q))
    )
  }
  if (filtroSimulado.value === 'si') base = base.filter(m => !!m.es_simulado)
  if (filtroSimulado.value === 'no') base = base.filter(m => !m.es_simulado)

  return base.slice(0, 60)
})

// Limpiar filtros dependientes al cambiar el padre
watch(filtroCentro,  () => { filtroFamilia.value = ''; filtroCiclo.value = ''; filtroCurso.value = '' })
watch(filtroFamilia, () => { filtroCiclo.value = ''; filtroCurso.value = '' })
watch(filtroCiclo,   () => { filtroCurso.value = '' })

// ── Abrir buscador y cargar catálogo (una sola vez) ───────────────────────────
async function abrirBuscador() {
  mostrarBuscador.value = true
  if (catalogoCargado.value) return
  cargandoCatalogo.value = true
  try {
    const [resMicroretos, resCentros] = await Promise.all([
      api.get('/microretos'),
      api.get('/centros'),
    ])
    todosMicroretos.value = resMicroretos.data.filter(m => {
      const centro  = m.centro_educativo || m.centro
      const familia = m.familia
      return (
        centro  && centro  !== 'Centro Desconocido' &&
        familia && familia !== 'Familia Desconocida'
      )
    })
    todosCentros.value = resCentros.data
    catalogoCargado.value = true
  } catch (e) {
    console.error('Error cargando catálogo:', e)
  } finally {
    cargandoCatalogo.value = false
  }
}

function cerrarBuscador() {
  mostrarBuscador.value = false
}

function normalizarCurso(curso) {
  if (!curso && curso !== 0) return ''
  const n = parseInt(curso)
  return n === 1 ? '1º' : n === 2 ? '2º' : String(curso)
}

function seleccionarMicroreto(m) {
  microretoSeleccionado.value = m
  form.value.microreto_id     = m.uuid || m.id
  form.value.microreto_titulo = m.titulo
  mostrarBuscador.value = false
}

// ─── Cargar microreto desde la URL (?microreto_id=...) ────────────────────────
onMounted(async () => {
  await cargarSesiones()

  // Migración silenciosa desde localStorage (una sola vez)
  try {
    const LEGACY_KEY = 'dualab_sesiones'
    const local = JSON.parse(localStorage.getItem(LEGACY_KEY) || '[]')
    if (local.length > 0) {
      const payload = local.map(s => ({
        microreto_id:     s.microreto_id     || null,
        microreto_titulo: s.microreto_titulo || '(sin título)',
        fecha:            s.fecha,
        centro_educativo: s.centro_educativo || null,
        ciclo_formativo:  s.ciclo_formativo  || null,
        curso:            s.curso            || null,
        grupo:            s.grupo            || null,
        num_alumnos:      s.num_alumnos ? Number(s.num_alumnos) : null,
        notas:            s.notas            || null,
      }))
      await api.post('/sesiones/lote', { sesiones: payload })
      localStorage.removeItem(LEGACY_KEY)
      await cargarSesiones()
    }
  } catch (e) {
    console.error('Error migrando sesiones desde localStorage:', e)
  }

  const id = route.query.microreto_id
  if (id) {
    cargandoMicroreto.value = true
    try {
      const res = await api.get(`/microretos/${id}`)
      microretoSeleccionado.value = res.data
      form.value.microreto_id     = res.data.id
      form.value.microreto_titulo = res.data.titulo
    } catch (e) {
      console.error('Error cargando microreto:', e)
    } finally {
      cargandoMicroreto.value = false
    }
  }
  await nextTick()
  pasoGuia.value = 1
  modoGuia.value = true
})

onUnmounted(() => {
  tourActivo.value = false
  window.removeEventListener('scroll', onScrollGuia)
})

// ─── Guardar sesión ───────────────────────────────────────────────────────────
async function guardarSesion() {
  if (!form.value.microreto_titulo || !form.value.fecha) return
  guardando.value = true
  try {
    const payload = {
      ...form.value,
      num_alumnos: form.value.num_alumnos !== '' ? Number(form.value.num_alumnos) : null,
    }
    const res = await api.post('/sesiones', payload)
    sesiones.value = [res.data, ...sesiones.value]
    guardadoOk.value = true
    setTimeout(() => { guardadoOk.value = false }, 2500)
  } catch (e) {
    console.error('Error guardando sesión:', e)
  } finally {
    guardando.value = false
  }
}

// ─── Eliminar sesión ──────────────────────────────────────────────────────────
async function eliminarSesion(id) {
  sesiones.value = sesiones.value.filter(s => s.id !== id)
  try {
    await api.delete(`/sesiones/${id}`)
  } catch (e) {
    console.error('Error eliminando sesión:', e)
    await cargarSesiones() // revertir si falla
  }
}

// ─── Filtros y paginación del panel de sesiones ───────────────────────────────
const filtroSes      = ref({ fecha: '', titulo: '', curso: '', grupo: '' })
const paginaSesiones = ref(1)
const SESIONES_POR_PAGINA = 5

const sesionesFiltradas = computed(() => {
  let lista = [...sesiones.value]
  if (filtroSes.value.fecha)
    lista = lista.filter(s => s.fecha === filtroSes.value.fecha)
  if (filtroSes.value.titulo.trim()) {
    const q = filtroSes.value.titulo.trim().toLowerCase()
    lista = lista.filter(s => (s.microreto_titulo || '').toLowerCase().includes(q))
  }
  if (filtroSes.value.curso) lista = lista.filter(s => s.curso === filtroSes.value.curso)
  if (filtroSes.value.grupo) lista = lista.filter(s => s.grupo === filtroSes.value.grupo)
  return lista
})

const sesionesVisibles = computed(() => {
  const start = (paginaSesiones.value - 1) * SESIONES_POR_PAGINA
  return sesionesFiltradas.value.slice(start, start + SESIONES_POR_PAGINA)
})

const totalPaginasSes = computed(() =>
  Math.ceil(sesionesFiltradas.value.length / SESIONES_POR_PAGINA)
)

watch(filtroSes, () => { paginaSesiones.value = 1 }, { deep: true })

// ─── Limpiar selección de microreto ──────────────────────────────────────────
function limpiarFormulario() {
  microretoSeleccionado.value  = null
  form.value.microreto_id      = ''
  form.value.microreto_titulo  = ''
  autocompletados.value        = { centro_educativo: false, ciclo_formativo: false, curso: false }
  router.replace({ path: '/dashboard' })
}

// ─── Modal de bienvenida "¿Qué necesitas?" ───────────────────────────────────
const guiaBienvenida = ref(false)
const modoGuia       = ref(false)
const pasoGuia       = ref(1)

const guiaPasosData = [
  { ref: 'refMicroreto',     seccion: 'microreto', texto: 'Busca y elige el reto que vas a trabajar con tu grupo. Este es el punto de partida.' },
  { ref: 'refCampoFecha',    seccion: 'datos',     texto: 'Indica la fecha en la que se realizó la sesión.' },
  { ref: 'refCampoCentro',   seccion: 'datos',     texto: 'Indica el centro educativo.' },
  { ref: 'refCampoAlumnado', seccion: 'datos',     texto: 'Indica la información del alumnado: curso, grupo y número de alumnos.' },
  { ref: 'refCampoNotas',    seccion: 'datos',     texto: 'Indica cualquier nota relevante sobre la sesión.' },
  { ref: 'refBtnGuardar',    seccion: 'datos',     texto: 'Pulsa guardar sesión para registrar la información.' },
  { ref: 'refSesiones',      seccion: 'sesiones',  texto: 'Quedará disponible para crear el proyecto y comenzar el taller de ideas, o consultar esta info más adelante.' },
  { ref: 'refBtnGuia',       seccion: null,        texto: 'Pincha aquí para volver a activar la guía cuando quieras.' },
]

const TOTAL_PASOS_GUIA = guiaPasosData.length

// Template refs para el tour
const refMicroreto     = ref(null)
const refCampoFecha    = ref(null)
const refCampoCentro   = ref(null)
const refCampoAlumnado = ref(null)
const refCampoNotas    = ref(null)
const refBtnGuardar    = ref(null)
const refSesiones      = ref(null)
const refBtnGuia       = ref(null)

const tourRefs = { refMicroreto, refCampoFecha, refCampoCentro, refCampoAlumnado, refCampoNotas, refBtnGuardar, refSesiones, refBtnGuia }

const tourTargetActivo = computed(() => {
  if (!modoGuia.value) return null
  return guiaPasosData[pasoGuia.value - 1]?.ref ?? null
})

const seccionActiva = computed(() => {
  if (!modoGuia.value) return null
  return guiaPasosData[pasoGuia.value - 1]?.seccion ?? null
})

const bocadilloPos = ref({ top: 0, left: 0, arrowLeft: 130, dir: 'top' })

function recalcularBocadillo() {
  const paso = guiaPasosData[pasoGuia.value - 1]
  if (!paso) return
  const el = tourRefs[paso.ref]?.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  const TOOLTIP_W = 272
  const TOOLTIP_H = 155
  const MARGIN    = 12
  const WIN_W = window.innerWidth
  const WIN_H = window.innerHeight

  // Usar sólo la parte visible del elemento en el viewport
  const visibleTop    = Math.max(0, rect.top)
  const visibleBottom = Math.min(WIN_H, rect.bottom)
  const centerX       = rect.left + rect.width / 2

  let tooltipLeft = centerX - TOOLTIP_W / 2
  tooltipLeft = Math.max(10, Math.min(tooltipLeft, WIN_W - TOOLTIP_W - 10))
  const arrowLeft = Math.max(18, Math.min(centerX - tooltipLeft, TOOLTIP_W - 18))

  let dir, tooltipTop
  if (WIN_H - visibleBottom > TOOLTIP_H + MARGIN) {
    // Hay espacio debajo de la parte visible → bocadillo abajo
    dir = 'top'
    tooltipTop = visibleBottom + MARGIN
  } else if (visibleTop > TOOLTIP_H + MARGIN) {
    // Hay espacio arriba de la parte visible → bocadillo arriba
    dir = 'bottom'
    tooltipTop = visibleTop - MARGIN - TOOLTIP_H
  } else {
    // Fallback: posicionar en el 60% del viewport
    dir = 'top'
    tooltipTop = WIN_H * 0.6
  }
  // Clamp final para garantizar que siempre queda dentro del viewport
  tooltipTop = Math.max(10, Math.min(tooltipTop, WIN_H - TOOLTIP_H - 10))

  bocadilloPos.value = { top: tooltipTop, left: tooltipLeft, arrowLeft, dir }
}

function seleccionarOpcionBienvenida(opcion) {
  guiaBienvenida.value = false
  if (opcion === 'sesiones') {
    router.push({ name: 'sesiones-registradas' })
  } else if (opcion === 'guia') {
    modoGuia.value = true
    pasoGuia.value = 1
  }
}

function avanzarGuia() {
  if (pasoGuia.value < TOTAL_PASOS_GUIA) {
    pasoGuia.value++
  } else {
    modoGuia.value = false
  }
}

function saltarGuia() {
  modoGuia.value = false
}

function scrollYRecalcular() {
  const paso = guiaPasosData[pasoGuia.value - 1]
  const el = tourRefs[paso?.ref]?.value
  if (el) el.scrollIntoView({ behavior: 'instant', block: 'nearest' })
  requestAnimationFrame(() => requestAnimationFrame(recalcularBocadillo))
}

function onScrollGuia() {
  if (modoGuia.value) requestAnimationFrame(recalcularBocadillo)
}

watch(pasoGuia, async () => {
  await nextTick()
  scrollYRecalcular()
})

watch(modoGuia, async (v) => {
  tourActivo.value = v
  if (v) {
    window.addEventListener('scroll', onScrollGuia, { passive: true })
    await nextTick()
    scrollYRecalcular()
  } else {
    window.removeEventListener('scroll', onScrollGuia)
  }
})

// Mostrar bienvenida de nuevo cuando se vuelve a navegar al dashboard
onBeforeRouteUpdate(async () => {
  modoGuia.value = false
  pasoGuia.value = 1
  await nextTick()
  modoGuia.value = true
})

// ─── Modal ficha de microreto ─────────────────────────────────────────────────
const microretoModalId = ref(null)

function abrirMicroretoModal(id) {
  microretoModalId.value = id
}

function cerrarMicroretoModal() {
  microretoModalId.value = null
}

// ─── Autocompletado de datos de sesión desde el microreto ────────────────────
const autocompletados = ref({ centro_educativo: false, ciclo_formativo: false, curso: false })

watch(microretoSeleccionado, (m) => {
  autocompletados.value = { centro_educativo: false, ciclo_formativo: false, curso: false }
  if (!m) return
  const centro = m.centro_educativo || m.centro
  if (centro)  { form.value.centro_educativo = centro;          autocompletados.value.centro_educativo = true }
  if (m.ciclo) { form.value.ciclo_formativo  = m.ciclo;         autocompletados.value.ciclo_formativo  = true }
  if (m.curso) { form.value.curso            = normalizarCurso(m.curso); autocompletados.value.curso = true }
})

// Opciones para datalist — combinan historial guardado + catálogo si ya fue cargado
const centrosParaAutocompletar = computed(() => {
  const set = new Set()
  sesiones.value.forEach(s => { if (s.centro_educativo) set.add(s.centro_educativo) })
  todosCentros.value.forEach(c => { if (c.nombre) set.add(c.nombre) })
  return [...set].sort()
})

const ciclosParaAutocompletar = computed(() => {
  const set = new Set()
  sesiones.value.forEach(s => { if (s.ciclo_formativo) set.add(s.ciclo_formativo) })
  todosMicroretos.value.forEach(m => { if (m.ciclo) set.add(m.ciclo) })
  return [...set].sort()
})

// ─── Modal de detalle de sesión ───────────────────────────────────────────────
const sesionAbierta = ref(null)

function verSesion(s) {
  sesionAbierta.value = s
}

function cerrarSesionModal() {
  sesionAbierta.value = null
}

function irAMicroreto(id) {
  router.push({ name: 'detalle-microreto', params: { id } })
}

const formularioValido = computed(() =>
  form.value.microreto_titulo.trim() !== '' && form.value.fecha !== ''
)

function formatFecha(isoDate) {
  if (!isoDate) return ''
  const d = new Date(isoDate + 'T12:00:00')
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' })
}
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] font-sans text-[#1F2937]">

    <!-- ══════════ MODAL BIENVENIDA "¿QUÉ NECESITAS?" ══════════════════════════ -->
    <BienvenidaModal :show="guiaBienvenida" @seleccionar="seleccionarOpcionBienvenida" />

    <!-- ══════════ TOUR BOCADILLO - OVERLAY + TOOLTIP ════════════════════════ -->
    <Transition name="sp-fade">
      <div v-if="modoGuia" class="fixed inset-0 z-[9990] pointer-events-none">
        <!-- Backdrop bloqueante transparente — bloquea interacción sin oscurecer el elemento activo -->
        <div class="absolute inset-0 pointer-events-auto" />

        <!-- Bocadillo flotante posicionado sobre el elemento activo -->
        <div class="absolute pointer-events-auto"
             :style="{ top: bocadilloPos.top + 'px', left: bocadilloPos.left + 'px', width: '272px', zIndex: 9992 }">

          <!-- Flecha apuntando arriba (bocadillo debajo del elemento) -->
          <div v-if="bocadilloPos.dir === 'top'"
               class="absolute -top-[10px] w-0 h-0"
               :style="{
                 left: bocadilloPos.arrowLeft + 'px',
                 transform: 'translateX(-50%)',
                 borderLeft: '9px solid transparent',
                 borderRight: '9px solid transparent',
                 borderBottom: '10px solid #1a2332'
               }" />

          <!-- Contenido -->
          <div class="bg-[#1a2332] border border-white/15 rounded-2xl shadow-2xl p-4 text-white">
            <!-- Progreso -->
            <div class="flex items-center justify-between mb-2.5">
              <div class="flex gap-1 items-center">
                <span v-for="i in TOTAL_PASOS_GUIA" :key="i"
                      class="h-[3px] rounded-full transition-all duration-300"
                      :class="i <= pasoGuia ? 'bg-[#00A859] w-5' : 'bg-white/20 w-3'" />
              </div>
              <span class="text-[9px] font-bold text-white/40">{{ pasoGuia }}/{{ TOTAL_PASOS_GUIA }}</span>
            </div>

            <p class="text-[11px] text-white/85 leading-relaxed mb-3">{{ guiaPasosData[pasoGuia - 1].texto }}</p>

            <div class="flex items-center gap-2">
              <button @click="avanzarGuia"
                      class="flex-1 py-1.5 rounded-xl bg-[#00A859] text-white
                             text-[9px] font-black uppercase tracking-widest
                             hover:bg-[#00A859]/90 transition-all">
                {{ pasoGuia < TOTAL_PASOS_GUIA ? 'Siguiente →' : 'Finalizar' }}
              </button>
              <button @click="saltarGuia"
                      class="px-2.5 py-1.5 rounded-xl bg-white/5 border border-white/10
                             text-white/40 text-[9px] font-black uppercase tracking-widest
                             hover:text-white/60 transition-all">
                Saltar
              </button>
            </div>
          </div>

          <!-- Flecha apuntando abajo (bocadillo encima del elemento) -->
          <div v-if="bocadilloPos.dir === 'bottom'"
               class="absolute -bottom-[10px] w-0 h-0"
               :style="{
                 left: bocadilloPos.arrowLeft + 'px',
                 transform: 'translateX(-50%)',
                 borderLeft: '9px solid transparent',
                 borderRight: '9px solid transparent',
                 borderTop: '10px solid #1a2332'
               }" />
        </div>
      </div>
    </Transition>

    <!-- Fondo decorativo -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[600px] h-[400px]
                bg-[#00A859] opacity-5 blur-[120px] rounded-full pointer-events-none z-0" />

    <div class="relative z-10 max-w-5xl mx-auto px-4 py-8 md:px-8 md:py-12">

      <!-- ─── Cabecera ─────────────────────────────────────────────────────── -->
      <header class="mb-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-5">
          <div>
            <div class="inline-flex items-center gap-2 mb-2 px-3 py-1 rounded-full
                        bg-[#00A859]/10 border border-[#00A859]/20">
              <span class="w-2 h-2 rounded-full bg-[#00A859]" />
              <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">Dashboard docente</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black tracking-tight text-[#121212]">
              Sesiones <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00A859] to-[#99CC33]">DuaLab</span>
            </h1>
            <p class="text-gray-500 text-sm mt-1">Registra y consulta tus sesiones de trabajo con retos.</p>
          </div>

          <!-- Stats chips -->
          <div class="flex flex-wrap gap-3 items-center">
            <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-2xl border border-gray-100 shadow-sm">
              <svg class="w-4 h-4 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                         M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
              </svg>
              <span class="font-black text-xl text-[#1F2937]">{{ sesiones.length }}</span>
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">sesiones</span>
            </div>

            <!-- Botón activar guía -->
            <button ref="refBtnGuia"
                    @click="modoGuia = true; pasoGuia = 1"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-50 rounded-2xl border border-blue-100
                           shadow-sm text-blue-500 text-xs font-black uppercase tracking-wider
                           hover:bg-blue-100 hover:border-blue-200 transition-all">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Activar Guía
            </button>
            <div v-if="microretoSeleccionado"
                 class="flex items-center gap-2 px-4 py-2 bg-[#00A859]/5 rounded-2xl border border-[#00A859]/20 shadow-sm">
              <span class="w-2 h-2 rounded-full bg-[#00A859]" />
              <span class="text-xs font-black text-[#00A859] uppercase tracking-wider truncate max-w-[180px]">
                {{ microretoSeleccionado.titulo }}
              </span>
            </div>
          </div>
        </div>
      </header>

      <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        <!-- ─── COLUMNA IZQUIERDA: Formulario ───────────────────────────── -->
        <div class="lg:col-span-3 space-y-4">

          <!-- ══ SELECTOR DE MICRORETO ══════════════════════════════════════ -->
          <div ref="refMicroreto"
               class="bg-white rounded-[1.5rem] border border-gray-100 shadow-sm overflow-hidden"
               :class="{
                 'tour-active':      tourTargetActivo === 'refMicroreto',
                 'tour-seccion-blur': modoGuia && seccionActiva !== null && seccionActiva !== 'microreto'
               }">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
              <p class="text-[10px] font-black uppercase tracking-[0.18em] text-gray-400">
                Reto a trabajar
              </p>
              <button v-if="!mostrarBuscador && !microretoSeleccionado"
                      @click="abrirBuscador"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl
                             bg-[#00A859]/10 border border-[#00A859]/20 text-[#00A859]
                             text-[10px] font-black uppercase tracking-widest
                             hover:bg-[#00A859]/20 transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                </svg>
                Buscar reto
              </button>
            </div>

            <div class="px-6 py-5">

              <!-- Cargando desde URL -->
              <div v-if="cargandoMicroreto" class="flex items-center gap-3 text-gray-400">
                <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
                </svg>
                <span class="text-sm">Cargando reto...</span>
              </div>

              <!-- Microreto seleccionado -->
              <div v-else-if="microretoSeleccionado && !mostrarBuscador"
                   class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="w-2 h-2 rounded-full bg-[#00A859] flex-shrink-0" />
                    <p class="text-xs font-black uppercase tracking-widest text-[#00A859]">
                      Seleccionado
                    </p>
                  </div>
                  <h3 class="font-black text-[#1F2937] text-base leading-snug">
                    {{ microretoSeleccionado.titulo }}
                  </h3>
                  <p class="text-xs text-gray-400 mt-1 flex flex-wrap gap-x-2">
                    <span v-if="microretoSeleccionado.familia">{{ microretoSeleccionado.familia }}</span>
                    <span v-if="microretoSeleccionado.ciclo">· {{ microretoSeleccionado.ciclo }}</span>
                    <span v-if="microretoSeleccionado.curso">· {{ normalizarCurso(microretoSeleccionado.curso) }}</span>
                  </p>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                  <button @click="abrirMicroretoModal(microretoSeleccionado.uuid || microretoSeleccionado.id)"
                          class="px-3 py-1.5 rounded-xl bg-gray-50 border border-gray-200
                                 text-[10px] font-black uppercase tracking-widest text-gray-500
                                 hover:border-[#00A859] hover:text-[#00A859] transition-all">
                    Ver ficha
                  </button>
                  <button @click="limpiarFormulario(); abrirBuscador()"
                          class="px-3 py-1.5 rounded-xl bg-gray-50 border border-gray-200
                                 text-[10px] font-black uppercase tracking-widest text-gray-400
                                 hover:border-[#00A859] hover:text-[#00A859] transition-all">
                    Cambiar
                  </button>
                </div>
              </div>

              <!-- ── PANEL BUSCADOR ──────────────────────────────────────── -->
              <div v-else-if="mostrarBuscador" class="space-y-4">

                <!-- Indicador de pasos -->
                <div class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest">
                  <span :class="filtroCentro ? 'text-[#00A859]' : 'text-gray-300'">① Centro <span class="text-red-400">*</span></span>
                  <span class="text-gray-200">›</span>
                  <span :class="filtroFamilia ? 'text-[#00A859]' : filtroCentro ? 'text-gray-400' : 'text-gray-200'">② Familia</span>
                  <span class="text-gray-200">›</span>
                  <span :class="filtroCiclo ? 'text-[#00A859]' : filtroCentro ? 'text-gray-400' : 'text-gray-200'">③ Ciclo</span>
                  <span class="text-gray-200">›</span>
                  <span :class="filtroCurso ? 'text-[#00A859]' : filtroCentro ? 'text-gray-400' : 'text-gray-200'">④ Curso</span>
                </div>

                <!-- Filtro: tipo de microreto (simulado / real) -->
                <div v-if="!cargandoCatalogo" class="flex items-center gap-2.5">
                  <p class="text-[9px] font-black uppercase tracking-widest text-gray-400 flex-shrink-0">Tipo:</p>
                  <div class="flex gap-1.5 flex-1">
                    <button v-for="op in [{ val: '', label: 'Todos' }, { val: 'si', label: 'Simulados' }, { val: 'no', label: 'Reales' }]"
                            :key="op.val"
                            @click="filtroSimulado = op.val"
                            class="flex-1 py-1 px-2 rounded-lg text-[9px] font-black uppercase tracking-widest border transition-all text-center"
                            :class="filtroSimulado === op.val
                              ? (op.val === 'si' ? 'bg-amber-400 border-amber-400 text-white' : 'bg-[#00A859] border-[#00A859] text-white')
                              : 'bg-gray-50 border-gray-200 text-gray-500 hover:border-gray-300 hover:text-gray-600'">
                      {{ op.label }}
                    </button>
                  </div>
                </div>

                <!-- Filtros en cascada -->
                <div class="grid grid-cols-2 gap-3">

                  <!-- ① Centro — siempre disponible -->
                  <div>
                    <label class="field-label flex items-center gap-1">
                      <span class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] font-black
                                   border" :class="filtroCentro ? 'bg-[#00A859] border-[#00A859] text-white' : 'border-gray-300 text-gray-400'">1</span>
                      Centro
                    </label>
                    <select v-model="filtroCentro" class="field-input">
                      <option value="">Selecciona un centro...</option>
                      <option v-for="c in centrosDisponibles" :key="c" :value="c">{{ c }}</option>
                    </select>
                  </div>

                  <!-- ② Familia — opcional, todas por defecto -->
                  <div>
                    <label class="field-label flex items-center gap-1">
                      <span class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] font-black
                                   border" :class="filtroFamilia ? 'bg-[#00A859] border-[#00A859] text-white' : filtroCentro ? 'border-gray-300 text-gray-400' : 'border-gray-200 text-gray-300'">2</span>
                      Familia profesional
                    </label>
                    <select v-model="filtroFamilia" class="field-input"
                            :disabled="!filtroCentro">
                      <option value="">Todas las familias</option>
                      <option v-for="f in familiasDisponibles" :key="f" :value="f">{{ f }}</option>
                    </select>
                  </div>

                  <!-- ③ Ciclo — opcional, todos por defecto -->
                  <div>
                    <label class="field-label flex items-center gap-1">
                      <span class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] font-black
                                   border" :class="filtroCiclo ? 'bg-[#00A859] border-[#00A859] text-white' : filtroCentro ? 'border-gray-300 text-gray-400' : 'border-gray-200 text-gray-300'">3</span>
                      Ciclo formativo
                    </label>
                    <select v-model="filtroCiclo" class="field-input"
                            :disabled="!filtroCentro">
                      <option value="">Todos los ciclos</option>
                      <option v-for="c in ciclosDisponibles" :key="c" :value="c">{{ c }}</option>
                    </select>
                  </div>

                  <!-- ④ Curso — opcional, todos por defecto -->
                  <div>
                    <label class="field-label flex items-center gap-1">
                      <span class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] font-black
                                   border" :class="filtroCurso ? 'bg-[#00A859] border-[#00A859] text-white' : filtroCentro ? 'border-gray-300 text-gray-400' : 'border-gray-200 text-gray-300'">4</span>
                      Curso
                    </label>
                    <div class="flex gap-2 mt-1" :class="!filtroCentro ? 'opacity-40 pointer-events-none' : ''">
                      <button v-for="op in ['', '1º', '2º']" :key="op"
                              @click="filtroCurso = op"
                              :disabled="!filtroCentro"
                              class="flex-1 py-2 rounded-xl text-[10px] font-black uppercase
                                     tracking-widest border transition-all disabled:cursor-not-allowed"
                              :class="filtroCurso === op
                                ? 'bg-[#00A859] border-[#00A859] text-white'
                                : 'bg-gray-50 border-gray-200 text-gray-500 hover:border-[#00A859]/40 hover:text-[#00A859]'">
                        {{ op === '' ? 'Todos' : op }}
                      </button>
                    </div>
                  </div>

                </div>

                <!-- Barra de búsqueda por texto — cuando hay centro o filtro simulado activo -->
                <div v-if="filtroCentro || filtroSimulado" class="relative">
                  <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300
                               pointer-events-none"
                       fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                  </svg>
                  <input v-model="busqueda"
                         type="text"
                         placeholder="Refinar por título..."
                         class="field-input pl-10" />
                </div>

                <!-- Contador resultados + limpiar -->
                <div class="flex items-center justify-between">
                  <p class="text-[10px] text-gray-400 font-medium">
                    <span v-if="cargandoCatalogo">Cargando catálogo...</span>
                    <span v-else-if="filtroCentro || filtroSimulado">
                      {{ resultadosFiltrados.length }}
                      {{ resultadosFiltrados.length === 1 ? 'reto' : 'retos' }}
                      encontrados
                    </span>
                    <span v-else class="text-gray-300">Selecciona un centro para ver resultados</span>
                  </p>
                  <div class="flex gap-2">
                    <button v-if="filtroCentro || filtroFamilia || filtroCiclo || filtroCurso || busqueda || filtroSimulado"
                            @click="filtroCentro=''; filtroFamilia=''; filtroCiclo=''; filtroCurso=''; busqueda=''; filtroSimulado=''"
                            class="text-[10px] font-black uppercase tracking-widest text-gray-400
                                   hover:text-red-400 transition-colors">
                      Limpiar filtros
                    </button>
                    <button @click="cerrarBuscador"
                            class="text-[10px] font-black uppercase tracking-widest text-gray-400
                                   hover:text-gray-600 transition-colors">
                      Cancelar
                    </button>
                  </div>
                </div>

                <!-- Spinner mientras carga -->
                <div v-if="cargandoCatalogo"
                     class="flex justify-center py-8">
                  <svg class="animate-spin w-6 h-6 text-[#00A859]" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
                  </svg>
                </div>

                <!-- Lista de resultados — cuando hay centro elegido o filtro simulado activo -->
                <ul v-else-if="(filtroCentro || filtroSimulado) && resultadosFiltrados.length"
                    class="space-y-2 max-h-72 overflow-y-auto pr-1 -mr-1">
                  <li v-for="m in resultadosFiltrados" :key="m.uuid || m.id">
                    <button @click="seleccionarMicroreto(m)"
                            class="w-full text-left px-4 py-3 rounded-xl border border-gray-100
                                   bg-gray-50 hover:border-[#00A859]/40 hover:bg-[#00A859]/5
                                   transition-all group">
                      <p class="text-sm font-black text-[#1F2937] leading-snug
                                group-hover:text-[#00A859] transition-colors line-clamp-2">
                        {{ m.titulo }}
                      </p>
                      <div class="flex flex-wrap gap-1.5 mt-1.5">
                        <span v-if="m.familia" class="tag tag-gray">{{ m.familia }}</span>
                        <span v-if="m.ciclo"   class="tag tag-gray truncate max-w-[180px]">{{ m.ciclo }}</span>
                        <span v-if="m.curso"   class="tag tag-green">{{ normalizarCurso(m.curso) }}</span>
                        <span v-if="m.es_simulado" class="tag tag-amber">Simulado</span>
                        <span v-if="m.centro_educativo || m.centro"
                              class="tag tag-gray">
                          {{ m.centro_educativo || m.centro }}
                        </span>
                      </div>
                    </button>
                  </li>
                </ul>

                <!-- Sin resultados (con centro o filtro simulado activo pero sin matches) -->
                <div v-else-if="filtroCentro || filtroSimulado"
                     class="text-center py-8 text-gray-400">
                  <svg class="w-8 h-8 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                  </svg>
                  <p class="text-xs font-medium">Sin resultados para esos filtros</p>
                </div>

                <!-- Indicador visual cuando el buscador está esperando que se elija un centro -->
                <div v-else-if="!filtroCentro && !filtroSimulado && !cargandoCatalogo"
                     class="rounded-xl border border-dashed border-gray-200 py-8 text-center">
                  <div class="flex items-center justify-center gap-1.5 text-gray-300 mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                  </div>
                  <p class="text-xs text-gray-400 font-medium">
                    Selecciona un centro educativo<br>
                    <span class="text-gray-300">para ver los retos disponibles</span>
                  </p>
                </div>

              </div>

              <!-- Estado inicial sin buscador abierto ni microreto -->
              <div v-else class="space-y-4">

                <!-- Aviso informativo -->
                <div class="flex items-start gap-3 p-3 rounded-xl bg-amber-50 border border-amber-100">
                  <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none"
                       stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <p class="text-xs text-amber-700 font-medium leading-relaxed">
                    Usa el buscador de retos o ve a la
                    <button @click="router.push('/biblioteca')"
                            class="underline font-black hover:text-amber-900 transition-colors">
                      Biblioteca
                    </button>
                    y pulsa "Trabajar este reto".
                  </p>
                </div>

                <!-- Botón principal de búsqueda, debajo del texto amarillo -->
                <button @click="abrirBuscador()"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl
                               bg-[#00A859] text-white font-black text-xs uppercase tracking-widest
                               hover:bg-[#00A859]/90 transition-all shadow-sm">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                  </svg>
                  Buscar reto
                </button>

                <!-- Separador -->
                <div class="relative flex items-center">
                  <div class="flex-1 border-t border-gray-100"/>
                  <span class="px-3 text-[10px] font-black uppercase tracking-widest text-gray-400 bg-white">
                    O utiliza el buscador
                  </span>
                  <div class="flex-1 border-t border-gray-100"/>
                </div>

                <!-- Barra de búsqueda estilizada que abre el modal -->
                <div @click="abrirBuscador()"
                     class="flex items-center gap-3 px-4 py-3 rounded-xl border border-gray-200
                            bg-gray-50 cursor-pointer hover:border-[#00A859]/40 hover:bg-[#00A859]/5
                            transition-all group">
                  <svg class="w-4 h-4 text-gray-300 group-hover:text-[#00A859] flex-shrink-0 transition-colors"
                       fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                  </svg>
                  <span class="text-sm text-gray-400 group-hover:text-[#00A859] transition-colors">
                    Buscar por título, ciclo, familia, curso...
                  </span>
                </div>

              </div>

            </div>
          </div>

          <!-- ══ DATOS DE LA SESIÓN ══════════════════════════════════════════ -->
          <div class="bg-white rounded-[1.5rem] border border-gray-100 shadow-sm overflow-hidden"
               :class="{ 'tour-seccion-blur': modoGuia && seccionActiva !== null && seccionActiva !== 'datos' }">
            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
              <p class="text-[10px] font-black uppercase tracking-[0.18em] text-gray-400">
                Datos de la sesión
              </p>
              <span v-if="autocompletados.centro_educativo || autocompletados.ciclo_formativo || autocompletados.curso"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full
                           bg-[#00A859]/10 border border-[#00A859]/20 text-[#00A859]
                           text-[8px] font-black uppercase tracking-widest">
                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                Autocompletado
              </span>
            </div>
            <div class="px-6 py-5 space-y-4">

              <div ref="refCampoFecha" :class="{ 'tour-active': tourTargetActivo === 'refCampoFecha' }"
                   class="rounded-xl transition-all">
                <label class="field-label">Fecha de trabajo <span class="text-red-400">*</span></label>
                <input v-model="form.fecha" type="date" class="field-input" />
              </div>

              <div ref="refCampoCentro" :class="{ 'tour-active': tourTargetActivo === 'refCampoCentro' }"
                   class="rounded-xl transition-all">
                <label class="field-label flex items-center gap-2">
                  Centro educativo
                  <span v-if="autocompletados.centro_educativo"
                        class="text-[8px] font-black bg-[#00A859]/10 text-[#00A859] px-1.5 py-0.5 rounded-full uppercase tracking-widest">
                    Auto
                  </span>
                </label>
                <input v-model="form.centro_educativo" type="text" list="centros-datalist"
                       @input="autocompletados.centro_educativo = false"
                       placeholder="Ej. IES Aguas Nuevas" class="field-input" />
                <datalist id="centros-datalist">
                  <option v-for="c in centrosParaAutocompletar" :key="c" :value="c" />
                </datalist>
              </div>

              <div>
                <label class="field-label flex items-center gap-2">
                  Ciclo formativo
                  <span v-if="autocompletados.ciclo_formativo"
                        class="text-[8px] font-black bg-[#00A859]/10 text-[#00A859] px-1.5 py-0.5 rounded-full uppercase tracking-widest">
                    Auto
                  </span>
                </label>
                <input v-model="form.ciclo_formativo" type="text" list="ciclos-datalist"
                       @input="autocompletados.ciclo_formativo = false"
                       placeholder="Ej. CFGM Sistemas Microinformáticos" class="field-input" />
                <datalist id="ciclos-datalist">
                  <option v-for="c in ciclosParaAutocompletar" :key="c" :value="c" />
                </datalist>
              </div>

              <div ref="refCampoAlumnado" class="space-y-4 rounded-xl transition-all"
                   :class="{ 'tour-active': tourTargetActivo === 'refCampoAlumnado' }">
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="field-label flex items-center gap-2">
                      Curso
                      <span v-if="autocompletados.curso"
                            class="text-[8px] font-black bg-[#00A859]/10 text-[#00A859] px-1.5 py-0.5 rounded-full uppercase tracking-widest">
                        Auto
                      </span>
                    </label>
                    <div class="flex gap-2 mt-1">
                      <button v-for="c in ['1º', '2º']" :key="c"
                              @click="form.curso = c; autocompletados.curso = false"
                              class="flex-1 py-2 rounded-xl text-xs font-black uppercase tracking-widest
                                     border transition-all"
                              :class="form.curso === c
                                ? 'bg-[#00A859] border-[#00A859] text-white shadow-sm'
                                : 'bg-gray-50 border-gray-200 text-gray-500 hover:border-[#00A859]/40 hover:text-[#00A859]'">
                        {{ c }}
                      </button>
                    </div>
                  </div>
                  <div>
                    <label class="field-label">Grupo</label>
                    <div class="flex gap-1.5 mt-1 flex-wrap">
                      <button v-for="g in ['A', 'B', 'C', 'D']" :key="g"
                              @click="form.grupo = g"
                              class="flex-1 min-w-[2rem] py-2 rounded-xl text-xs font-black uppercase
                                     border transition-all"
                              :class="form.grupo === g
                                ? 'bg-[#99CC33] border-[#99CC33] text-white shadow-sm'
                                : 'bg-gray-50 border-gray-200 text-gray-500 hover:border-[#99CC33]/40 hover:text-[#99CC33]'">
                        {{ g }}
                      </button>
                    </div>
                  </div>
                </div>
                <div>
                  <label class="field-label">Número de alumnos</label>
                  <input v-model="form.num_alumnos" type="number" min="1" max="99"
                         placeholder="Ej. 24" class="field-input w-32" />
                </div>
              </div>

              <div ref="refCampoNotas" class="rounded-xl transition-all"
                   :class="{ 'tour-active': tourTargetActivo === 'refCampoNotas' }">
                <label class="field-label">Notas adicionales</label>
                <textarea v-model="form.notas" rows="3"
                          placeholder="Observaciones, adaptaciones realizadas, valoración..."
                          class="field-input resize-none" />
              </div>

            </div>

            <div ref="refBtnGuardar"
                 class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-4 rounded-b-[1.5rem] transition-all"
                 :class="{ 'tour-active': tourTargetActivo === 'refBtnGuardar' }">
              <p class="text-[10px] text-gray-400 font-medium">
                La sesión se guarda en la base de datos.
              </p>
              <button @click="guardarSesion"
                      :disabled="!formularioValido || guardando"
                      class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl
                             text-xs font-black uppercase tracking-widest
                             transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                      :class="guardadoOk
                        ? 'bg-[#99CC33] text-white'
                        : 'bg-[#00A859] hover:bg-[#00A859]/90 text-white shadow-sm'">
                <svg v-if="guardadoOk" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <svg v-else-if="guardando" class="w-4 h-4 animate-spin" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3
                           m-4 0V3m0 4l-2-2m2 2l2-2"/>
                </svg>
                {{ guardadoOk ? '¡Guardado!' : guardando ? 'Guardando...' : 'Guardar sesión' }}
              </button>
            </div>
          </div>

        </div>

        <!-- ─── COLUMNA DERECHA: Historial ──────────────────────────────── -->
        <div class="lg:col-span-2 transition-all"
             :class="{ 'tour-seccion-blur': modoGuia && seccionActiva !== null && seccionActiva !== 'sesiones' }">
          <div ref="refSesiones"
               class="bg-white rounded-[1.5rem] border border-gray-100 shadow-sm overflow-hidden sticky top-6"
               :class="{ 'tour-active': tourTargetActivo === 'refSesiones' }">

            <!-- Cabecera -->
            <div class="px-5 py-4 border-b border-gray-50">
              <div class="flex items-center justify-between mb-3">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-gray-400">
                  Sesiones registradas
                </p>
                <span class="text-[10px] font-black bg-[#00A859]/10 text-[#00A859]
                             px-2 py-0.5 rounded-full uppercase tracking-widest">
                  {{ sesiones.length }}
                </span>
              </div>
              <!-- Botones de acción siempre visibles -->
              <div class="grid grid-cols-2 gap-2">
                <button @click="router.push('/dashboard/sesiones')"
                        class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl
                               bg-[#00A859]/8 border text-[#00A859]
                               text-[9px] font-black uppercase tracking-widest
                               hover:bg-[#00A859]/15 transition-all">
                  <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                  </svg>
                  Ver sesiones
                </button>
                <button @click="router.push({ name: 'startup-day-crear' })"
                        class="flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl
                               bg-[#99CC33]/10 border text-[#5a7a00]
                               text-[9px] font-black uppercase tracking-widest
                               hover:bg-[#99CC33]/20 transition-all">
                  <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                  </svg>
                  Nuevo proyecto
                </button>
              </div>
            </div>

            <!-- Filtros compactos -->
            <div v-if="sesiones.length > 0" class="px-4 py-3 border-b border-gray-50 space-y-2">
              <!-- Búsqueda por título -->
              <div class="relative">
                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3 h-3 text-gray-300 pointer-events-none"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                </svg>
                <input v-model="filtroSes.titulo" type="text" placeholder="Buscar sesión..."
                       class="w-full bg-gray-50 border border-gray-200 rounded-lg pl-7 pr-3 py-1.5
                              text-xs font-medium text-gray-700 placeholder-gray-300
                              focus:outline-none focus:border-[#00A859]/50 focus:ring-1 focus:ring-[#00A859]/20" />
              </div>
              <!-- Fecha -->
              <input v-model="filtroSes.fecha" type="date"
                     class="w-full bg-gray-50 border border-gray-200 rounded-lg px-2.5 py-1.5
                            text-xs font-medium text-gray-600
                            focus:outline-none focus:border-[#00A859]/50 focus:ring-1 focus:ring-[#00A859]/20" />
              <!-- Curso + Grupo -->
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <p class="text-[8px] font-black uppercase tracking-widest text-gray-400 mb-1">Curso</p>
                  <div class="flex gap-1">
                    <button v-for="c in ['', '1º', '2º']" :key="'fc'+c"
                            @click="filtroSes.curso = c"
                            class="flex-1 py-1 rounded-lg text-[8px] font-black uppercase border transition-all"
                            :class="filtroSes.curso === c
                              ? 'bg-[#00A859] border-[#00A859] text-white'
                              : 'bg-gray-50 border-gray-200 text-gray-400 hover:border-[#00A859]/40'">
                      {{ c === '' ? '·' : c }}
                    </button>
                  </div>
                </div>
                <div>
                  <p class="text-[8px] font-black uppercase tracking-widest text-gray-400 mb-1">Grupo</p>
                  <div class="flex gap-0.5">
                    <button v-for="g in ['', 'A', 'B', 'C', 'D']" :key="'fg'+g"
                            @click="filtroSes.grupo = g"
                            class="flex-1 py-1 rounded-md text-[7px] font-black uppercase border transition-all"
                            :class="filtroSes.grupo === g
                              ? 'bg-[#99CC33] border-[#99CC33] text-white'
                              : 'bg-gray-50 border-gray-200 text-gray-400 hover:border-[#99CC33]/40'">
                      {{ g === '' ? '·' : g }}
                    </button>
                  </div>
                </div>
              </div>
              <!-- Limpiar filtros -->
              <div v-if="filtroSes.titulo || filtroSes.fecha || filtroSes.curso || filtroSes.grupo"
                   class="flex justify-end">
                <button @click="filtroSes = { fecha: '', titulo: '', curso: '', grupo: '' }"
                        class="text-[9px] font-black uppercase tracking-widest text-gray-400 hover:text-red-400 transition-colors">
                  Limpiar filtros
                </button>
              </div>
            </div>

            <!-- Cargando sesiones -->
            <div v-if="cargandoSesiones" class="px-5 py-10 flex justify-center">
              <svg class="animate-spin w-5 h-5 text-[#00A859]" viewBox="0 0 24 24">
                <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
              </svg>
            </div>

            <!-- Estado vacío -->
            <div v-else-if="sesiones.length === 0" class="px-5 py-10 text-center">
              <div class="w-12 h-12 rounded-full bg-gray-50 border border-gray-100
                          flex items-center justify-center mx-auto mb-3">
                <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                </svg>
              </div>
              <p class="text-xs text-gray-400 font-medium leading-relaxed">
                Aún no hay sesiones.<br>¡Registra la primera!
              </p>
            </div>

            <!-- Sin resultados tras filtrar -->
            <div v-else-if="!cargandoSesiones && sesionesFiltradas.length === 0" class="px-5 py-8 text-center">
              <p class="text-xs text-gray-400 font-medium">Sin resultados para esos filtros.</p>
            </div>

            <!-- Lista de sesiones (miniaturas) -->
            <ul v-else-if="!cargandoSesiones" class="divide-y divide-gray-50">
              <li v-for="s in sesionesVisibles" :key="s.id"
                  class="px-4 py-3 hover:bg-gray-50/60 transition-colors group cursor-pointer"
                  @click="verSesion(s)">
                <div class="flex items-start justify-between gap-2">
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-black text-[#1F2937] leading-snug truncate
                              group-hover:text-[#00A859] transition-colors">
                      {{ s.microreto_titulo || '(sin título)' }}
                    </p>
                    <p class="text-[10px] text-[#00A859] font-bold mt-0.5">
                      {{ formatFecha(s.fecha) }}
                    </p>
                    <div class="flex flex-wrap gap-1 mt-1">
                      <span v-if="s.curso"       class="tag tag-green">{{ s.curso }}</span>
                      <span v-if="s.grupo"       class="tag tag-lime">Gr. {{ s.grupo }}</span>
                      <span v-if="s.num_alumnos" class="tag tag-gray">{{ s.num_alumnos }} al.</span>
                    </div>
                  </div>
                  <div class="flex flex-col items-end gap-1 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-all">
                    <button @click.stop="eliminarSesion(s.id)"
                            class="p-1 rounded-lg hover:bg-red-50 text-gray-300 hover:text-red-400 transition-all"
                            title="Eliminar">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </li>
            </ul>

            <!-- Paginación -->
            <div v-if="totalPaginasSes > 1"
                 class="px-4 py-3 border-t border-gray-50 flex items-center justify-between gap-2">
              <button @click="paginaSesiones--"
                      :disabled="paginaSesiones === 1"
                      class="p-1.5 rounded-lg border border-gray-200 text-gray-400
                             hover:border-[#00A859] hover:text-[#00A859]
                             disabled:opacity-30 disabled:cursor-not-allowed transition-all">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
              </button>
              <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                {{ paginaSesiones }} / {{ totalPaginasSes }}
              </span>
              <button @click="paginaSesiones++"
                      :disabled="paginaSesiones === totalPaginasSes"
                      class="p-1.5 rounded-lg border border-gray-200 text-gray-400
                             hover:border-[#00A859] hover:text-[#00A859]
                             disabled:opacity-30 disabled:cursor-not-allowed transition-all">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- MODAL DETALLE DE SESIÓN                                                -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="sesionAbierta"
           class="fixed inset-0 z-50 flex items-center justify-center p-4">

        <!-- Backdrop -->
        <div @click="cerrarSesionModal()"
             class="absolute inset-0 bg-black/40 backdrop-blur-sm" />

        <!-- Panel -->
        <div class="relative bg-white rounded-[1.75rem] shadow-2xl max-w-lg w-full overflow-hidden
                    border border-gray-100">

          <!-- Cabecera -->
          <div class="px-7 pt-7 pb-5 border-b border-gray-100">
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                  <span class="w-2 h-2 rounded-full bg-[#00A859] flex-shrink-0" />
                  <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#00A859]">
                    Sesión · {{ formatFecha(sesionAbierta.fecha) }}
                  </p>
                </div>
                <h2 class="text-lg font-black text-[#1F2937] leading-snug">
                  {{ sesionAbierta.microreto_titulo || '(sin título)' }}
                </h2>
              </div>
              <button @click="cerrarSesionModal()"
                      class="flex-shrink-0 p-2 rounded-xl hover:bg-gray-100 text-gray-400
                             hover:text-gray-600 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- Tags rápidos -->
            <div class="flex flex-wrap gap-1.5 mt-3">
              <span v-if="sesionAbierta.curso"           class="tag tag-green">{{ sesionAbierta.curso }}</span>
              <span v-if="sesionAbierta.grupo"           class="tag tag-lime">Grupo {{ sesionAbierta.grupo }}</span>
              <span v-if="sesionAbierta.num_alumnos"     class="tag tag-gray">{{ sesionAbierta.num_alumnos }} alumnos</span>
            </div>
          </div>

          <!-- Cuerpo -->
          <div class="px-7 py-5 space-y-4">

            <!-- Grid de datos -->
            <div class="grid grid-cols-2 gap-3">
              <div v-if="sesionAbierta.centro_educativo"
                   class="p-3.5 rounded-xl bg-[#F8FAFC] border border-gray-100">
                <p class="text-[9px] font-black uppercase tracking-wider text-gray-400 mb-0.5">Centro</p>
                <p class="text-sm font-bold text-[#1F2937] leading-snug">{{ sesionAbierta.centro_educativo }}</p>
              </div>
              <div v-if="sesionAbierta.ciclo_formativo"
                   class="p-3.5 rounded-xl bg-[#F8FAFC] border border-gray-100">
                <p class="text-[9px] font-black uppercase tracking-wider text-gray-400 mb-0.5">Ciclo</p>
                <p class="text-sm font-bold text-[#1F2937] leading-snug">{{ sesionAbierta.ciclo_formativo }}</p>
              </div>
              <div v-if="sesionAbierta.fecha"
                   class="p-3.5 rounded-xl bg-[#F8FAFC] border border-gray-100">
                <p class="text-[9px] font-black uppercase tracking-wider text-gray-400 mb-0.5">Fecha</p>
                <p class="text-sm font-bold text-[#1F2937]">{{ formatFecha(sesionAbierta.fecha) }}</p>
              </div>
              <div v-if="sesionAbierta.num_alumnos"
                   class="p-3.5 rounded-xl bg-[#F8FAFC] border border-gray-100">
                <p class="text-[9px] font-black uppercase tracking-wider text-gray-400 mb-0.5">Alumnos</p>
                <p class="text-sm font-bold text-[#1F2937]">{{ sesionAbierta.num_alumnos }}</p>
              </div>
            </div>

            <!-- Notas -->
            <div v-if="sesionAbierta.notas"
                 class="p-4 rounded-xl bg-[#F8FAFC] border border-gray-100">
              <p class="text-[9px] font-black uppercase tracking-wider text-gray-400 mb-1.5">Notas adicionales</p>
              <p class="text-sm text-[#1F2937] leading-relaxed">{{ sesionAbierta.notas }}</p>
            </div>

          </div>

          <!-- Pie -->
          <div class="px-7 py-4 bg-[#F8FAFC] border-t border-gray-100 flex items-center justify-between gap-4">
            <button @click="eliminarSesion(sesionAbierta.id); cerrarSesionModal()"
                    class="text-xs text-gray-400 hover:text-red-400 font-black uppercase
                           tracking-widest transition-colors">
              Eliminar sesión
            </button>
            <div v-if="sesionAbierta.microreto_id" class="flex items-center gap-2">
              <button @click="abrirMicroretoModal(sesionAbierta.microreto_id); cerrarSesionModal()"
                      class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl
                             bg-white border border-gray-200 text-gray-500
                             text-xs font-black uppercase tracking-widest
                             hover:border-[#00A859] hover:text-[#00A859] transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                </svg>
                Ver ficha
              </button>
              <button @click="router.push({ name: 'startup-day-crear', query: { microreto_id: sesionAbierta.microreto_id, sesion_id: sesionAbierta.id } }); cerrarSesionModal()"
                      class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#99CC33] text-white
                             text-xs font-black uppercase tracking-widest hover:bg-[#99CC33]/90
                             transition-all shadow-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Crear proyecto
              </button>
            </div>
          </div>

        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <!-- MODAL FICHA DE MICRORETO                                               -->
  <!-- ═══════════════════════════════════════════════════════════════════════ -->
  <MicroretoModal
    :microreto-id="microretoModalId"
    @close="cerrarMicroretoModal"
  />

</template>

<style scoped>
.field-label {
  display: block;
  font-size: 0.6875rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #6B7280;
  margin-bottom: 0.375rem;
}
.field-input {
  width: 100%;
  background: #F9FAFB;
  border: 1px solid #E5E7EB;
  border-radius: 0.75rem;
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #1F2937;
  outline: none;
  transition: border-color 150ms ease, box-shadow 150ms ease;
}
.field-input:focus {
  border-color: #00A859;
  box-shadow: 0 0 0 3px rgba(0, 168, 89, 0.12);
  background: #fff;
}
.field-input::placeholder { color: #D1D5DB; font-weight: 400; }
.field-input:disabled { opacity: 0.45; cursor: not-allowed; }
select.field-input { cursor: pointer; }
.tag {
  display: inline-flex;
  align-items: center;
  padding: 0.125rem 0.5rem;
  border-radius: 999px;
  font-size: 0.625rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}
.tag-gray  { background: #F3F4F6; color: #6B7280; }
.tag-green { background: rgba(0,168,89,0.1); color: #00A859; }
.tag-lime  { background: rgba(153,204,51,0.12); color: #5a7a00; }
.tag-amber { background: rgba(251,191,36,0.12); color: #92400e; }

/* Tour: sección activa */
.tour-active {
  box-shadow: 0 0 0 3px #00A859, 0 0 0 8px rgba(0, 168, 89, 0.2), 0 4px 20px rgba(0,0,0,0.1) !important;
  border-radius: 0.75rem;
  transition: box-shadow 0.25s ease;
}

/* Tour: secciones inactivas se desvanecen y desenfocadas */
.tour-seccion-blur {
  filter: blur(2px);
  opacity: 0.4;
  pointer-events: none;
  transition: filter 0.3s ease, opacity 0.3s ease;
}

/* sp-fade transition (overlay y bocadillo) */
.sp-fade-enter-active, .sp-fade-leave-active { transition: opacity 200ms ease; }
.sp-fade-enter-from, .sp-fade-leave-to { opacity: 0; }

/* Modal animation */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 200ms ease;
}
.modal-fade-enter-active .relative,
.modal-fade-leave-active .relative {
  transition: transform 200ms ease, opacity 200ms ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
.modal-fade-enter-from .relative {
  transform: scale(0.95) translateY(8px);
  opacity: 0;
}
.modal-fade-leave-to .relative {
  transform: scale(0.97);
  opacity: 0;
}
</style>
