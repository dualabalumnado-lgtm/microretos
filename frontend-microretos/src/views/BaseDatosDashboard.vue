<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import api from '../api.js'
import InsertModifyEmpresa from '../components/InsertModifyEmpresa.vue'
import CentroEducativoModal from '../components/CentroEducativoModal.vue'
import EliminarCentroModal from '../components/EliminarCentroModal.vue'
import EliminarEmpresaModal from '../components/EliminarEmpresaModal.vue'
import GestionFamiliasCiclosModal from '../components/GestionFamiliasCiclosModal.vue'

const authStore = useAuthStore()
const router = useRouter()

// ─── Aviso de expiración de sesión ───────────────────────
const minutosRestantes = ref(authStore.minutosRestantes)
let tokenTimer = null
onMounted(() => {
  tokenTimer = setInterval(() => {
    minutosRestantes.value = authStore.minutosRestantes
    if (minutosRestantes.value === 0) {
      clearInterval(tokenTimer)
      authStore.logout()
      router.push('/')
    }
  }, 60_000)
})
onUnmounted(() => clearInterval(tokenTimer))

const mostrarAvisoToken = computed(() =>
  minutosRestantes.value >= 0 && minutosRestantes.value <= 60
)

// ─── Datos ───────────────────────────────────────────────
const empresas             = ref([])
const centros              = ref([])   // todos los centros con sus ciclos
const familiasProfesionales = ref([])
const cargando             = ref(true)
const errorCarga           = ref(null)

// ─── Filtros ─────────────────────────────────────────────
const busqueda      = ref('')
const filtroFamilia = ref('')
const filtroCentro  = ref('')

// ─── Acordeón: qué centros/familias están abiertos ───────
const centrosExpandidos   = ref(new Set())
const familiasExpandidas  = ref(new Set())

// ─── Empresa expandida (panel de detalle inline) ─────────
const empresaExpandida = ref(null)

// ─── Modal CATÁLOGO FP (familias y ciclos) ────────────────
const mostrarConfirmCatalogo = ref(false)
const mostrarCatalogo        = ref(false)

function pedirAbrirCatalogo() {
  mostrarConfirmCatalogo.value = true
}
function confirmarAbrirCatalogo() {
  mostrarConfirmCatalogo.value = false
  mostrarCatalogo.value        = true
}

// ─── Modal CREAR / EDITAR CENTRO EDUCATIVO ───────────────
const mostrarNuevoCentro  = ref(false)
const mostrarEditarCentro = ref(false)
const centroAEditar       = ref(null)   // { id, nombre, ciclos[] }

function onCentroCreado(centro) {
  mostrarSnack(`Centro "${centro.nombre}" creado correctamente.`, 'ok')
  cargarDatos()
}

function pedirEditarCentro(centroNombre) {
  const datos = centros.value.find(c => c.nombre === centroNombre)
  if (!datos?.id) return
  centroAEditar.value       = datos        // { id, nombre, ciclos[] }
  mostrarEditarCentro.value = true
}

function onCentroGuardado(centro) {
  mostrarEditarCentro.value = false
  mostrarSnack(`Centro "${centro.nombre}" actualizado correctamente.`, 'ok')
  cargarDatos()
}

// ─── Modal ELIMINAR CENTRO ────────────────────────────────
const mostrarEliminarCentro = ref(false)
const centroAEliminar       = ref(null)   // { id, nombre, numEmpresas, numCiclos }

function pedirEliminarCentro(centroNombre) {
  const datos = centros.value.find(c => c.nombre === centroNombre)
  if (!datos?.id) return   // solo centros del catálogo (con id real)
  const numEmpresas = Object.values(datosPorCentro.value[centroNombre]?.familias ?? {})
    .reduce((n, f) => n + f.empresas.length, 0)
  centroAEliminar.value = {
    id:          datos.id,
    nombre:      datos.nombre,
    numEmpresas,
    numCiclos:   datos.ciclos.length,
  }
  mostrarEliminarCentro.value = true
}

function onCentroEliminado(centro) {
  mostrarEliminarCentro.value = false
  mostrarSnack(`Centro "${centro.nombre}" eliminado correctamente.`, 'ok')
  cargarDatos()
}

// ─── Modal NUEVA EMPRESA ──────────────────────────────────
const mostrarNuevaEmpresa = ref(false)

// ─── Modal EDITAR ─────────────────────────────────────────
const mostrarEditarEmpresa = ref(false)
const empresaAEditar       = ref(null)

// ─── Confirmación EDITAR ─────────────────────────────────
const mostrarConfirmEdit   = ref(false)
const empresaEditTemp      = ref(null)

// ─── Modal ELIMINAR EMPRESA ───────────────────────────────
const mostrarEliminarEmpresa = ref(false)
const empresaParaEliminar    = ref(null)

// ─── Snackbar de feedback ─────────────────────────────────
const snackbar = ref({ visible: false, mensaje: '', tipo: 'ok' })
function mostrarSnack(mensaje, tipo = 'ok') {
  snackbar.value = { visible: true, mensaje, tipo }
  setTimeout(() => { snackbar.value.visible = false }, 3500)
}

// ═══════════════════════════════════════════════════════════
//  CARGA INICIAL
// ═══════════════════════════════════════════════════════════
onMounted(async () => {
  if (!authStore.isAuthenticated) {
    router.push('/')
    return
  }
  await cargarDatos()
})

async function cargarDatos() {
  cargando.value  = true
  errorCarga.value = null
  try {
    const [resEmpresas, resFamilias, resCentros] = await Promise.all([
      api.get('/empresas/dashboard'),
      api.get('/familias'),
      api.get('/centros'),
    ])
    empresas.value              = resEmpresas.data
    familiasProfesionales.value = resFamilias.data
    centros.value               = resCentros.data

    // Expandir todos los centros y familias por defecto
    const todosLosCentros = [
      ...centros.value.map(c => c.nombre),
      ...empresas.value.map(e => e.centro_educativo).filter(Boolean),
    ]
    todosLosCentros.forEach(c => centrosExpandidos.value.add(c))
    const fams = [...new Set(empresas.value.flatMap(e => e.familias_nombres?.length ? e.familias_nombres : ['— Sin familia —']))]
    fams.forEach(f => familiasExpandidas.value.add(f))
  } catch (e) {
    if (e.response?.status === 401) {
      authStore.logout()
      router.push('/')
      return
    }
    errorCarga.value = 'No se pudieron cargar los datos. Comprueba la conexión e inténtalo de nuevo.'
    console.error(e)
  } finally {
    cargando.value = false
  }
}

// ═══════════════════════════════════════════════════════════
//  COMPUTED: stats y agrupación
// ═══════════════════════════════════════════════════════════
const totalEmpresas = computed(() => empresas.value.length)

// Centros del catálogo + legacy sin normalizar
const totalCentros = computed(() => {
  const nombres = new Set(centros.value.map(c => c.nombre))
  empresas.value.forEach(e => { if (e.centro_educativo) nombres.add(e.centro_educativo) })
  return nombres.size
})

const todasLasFamilias = computed(() =>
  [...new Set(empresas.value.flatMap(e => e.familias_nombres || []).filter(Boolean))].sort()
)

// Opciones del filtro: todos los centros (catálogo + legacy de empresas)
const todosCentros = computed(() => {
  const nombres = new Set(centros.value.map(c => c.nombre))
  empresas.value.forEach(e => { if (e.centro_educativo) nombres.add(e.centro_educativo) })
  return [...nombres].sort()
})

// Empresas sin centro (se muestran en la sección huérfanas, fuera del acordeón)
const empresasHuerfanas = computed(() => {
  const q = busqueda.value.toLowerCase().trim()
  return empresas.value.filter(e => {
    if (e.centro_educativo || e.centro_id) return false   // tiene centro → no es huérfana
    if (filtroCentro.value) return false                  // filtro de centro activo → ocultarlas
    if (q) {
      const coincide =
        e.nombre_comercial?.toLowerCase().includes(q) ||
        e.cif?.toLowerCase().includes(q) ||
        e.municipio?.toLowerCase().includes(q) ||
        e.persona_contacto?.toLowerCase().includes(q)
      if (!coincide) return false
    }
    if (filtroFamilia.value && !e.familias_nombres?.includes(filtroFamilia.value)) return false
    return true
  })
})

const empresasFiltradas = computed(() => {
  const q = busqueda.value.toLowerCase().trim()
  return empresas.value.filter(e => {
    if (!e.centro_educativo && !e.centro_id) return false  // huérfanas van a su propia sección
    if (q) {
      const coincide =
        e.nombre_comercial?.toLowerCase().includes(q) ||
        e.cif?.toLowerCase().includes(q) ||
        e.municipio?.toLowerCase().includes(q) ||
        e.persona_contacto?.toLowerCase().includes(q)
      if (!coincide) return false
    }
    if (filtroFamilia.value && !e.familias_nombres?.includes(filtroFamilia.value)) return false
    if (filtroCentro.value  && e.centro_educativo !== filtroCentro.value) return false
    return true
  })
})

/**
 * Estructura principal del acordeón.
 * { [centroNombre]: { id, familias: { [familiaName]: { ciclos: [], empresas: [] } } } }
 *
 * - Parte de TODOS los centros del catálogo (aunque no tengan empresas).
 * - Añade empresas filtradas encima.
 * - Con filtros activos, oculta centros sin empresas coincidentes
 *   (pero no oculta el centro si filtroCentro apunta directamente a él).
 */
const datosPorCentro = computed(() => {
  const mapa = {}

  // 1. Inicializar con todos los centros del catálogo y sus ciclos por familia
  const centrosBase = filtroCentro.value
    ? centros.value.filter(c => c.nombre === filtroCentro.value)
    : centros.value

  for (const centro of centrosBase) {
    mapa[centro.nombre] = { id: centro.id, familias: {} }
    for (const ciclo of centro.ciclos) {
      const fam = ciclo.familia_nombre || '— Sin familia asignada —'
      if (!mapa[centro.nombre].familias[fam])
        mapa[centro.nombre].familias[fam] = { ciclos: [], empresas: [] }
      mapa[centro.nombre].familias[fam].ciclos.push(ciclo)
    }
  }

  // 2. Incorporar empresas filtradas (incluyendo centros legacy sin normalizar)
  for (const e of empresasFiltradas.value) {
    const centroNombre = e.centro_educativo || '— Sin centro asignado —'
    if (!mapa[centroNombre]) mapa[centroNombre] = { id: null, familias: {} }
    const familias = e.familias_nombres?.length ? e.familias_nombres : ['— Sin familia asignada —']
    for (const fam of familias) {
      if (!mapa[centroNombre].familias[fam])
        mapa[centroNombre].familias[fam] = { ciclos: [], empresas: [] }
      mapa[centroNombre].familias[fam].empresas.push(e)
    }
  }

  // 3. Con búsqueda o filtro de familia activo, ocultar centros sin empresas coincidentes
  if ((busqueda.value || filtroFamilia.value) && !filtroCentro.value) {
    for (const nombre of Object.keys(mapa)) {
      const tieneEmpresas = Object.values(mapa[nombre].familias).some(f => f.empresas.length > 0)
      if (!tieneEmpresas) delete mapa[nombre]
    }
  }

  return mapa
})

const centrosOrdenados = computed(() => Object.keys(datosPorCentro.value).sort())

const totalFiltradas = computed(() => empresasFiltradas.value.length)

// ─── Colores de estado de contacto ──────────────────────
const ESTADO_BADGE = {
  'Pendiente de llamar':            { bg: 'bg-amber-100',       text: 'text-amber-700',   border: 'border-amber-300',   dot: 'bg-amber-400',   pulse: true  },
  'Llamado - Información obtenida': { bg: 'bg-[#00A859]/10',    text: 'text-[#00A859]',   border: 'border-[#00A859]/30',dot: 'bg-[#00A859]',   pulse: false },
  'Llamado - Negativa':             { bg: 'bg-red-50',          text: 'text-red-600',     border: 'border-red-200',     dot: 'bg-red-400',     pulse: false },
  'Llamado - Llamar más tarde':     { bg: 'bg-blue-50',         text: 'text-blue-600',    border: 'border-blue-200',    dot: 'bg-blue-400',    pulse: false },
  'En colaboración activa':         { bg: 'bg-[#1F2937]/8',     text: 'text-[#1F2937]',   border: 'border-gray-300',    dot: 'bg-[#1F2937]',   pulse: false },
  'Descartada':                     { bg: 'bg-gray-100',        text: 'text-gray-500',    border: 'border-gray-200',    dot: 'bg-gray-400',    pulse: false },
  // valores legacy en BD
  'Pendiente':                      { bg: 'bg-amber-100',       text: 'text-amber-700',   border: 'border-amber-300',   dot: 'bg-amber-400',   pulse: true  },
  'Pendiente ':                     { bg: 'bg-amber-100',       text: 'text-amber-700',   border: 'border-amber-300',   dot: 'bg-amber-400',   pulse: true  },
  'Email enviado sin respuesta':    { bg: 'bg-red-50',          text: 'text-red-500',     border: 'border-red-200',     dot: 'bg-red-300',     pulse: false },
  'Email enviado pendiente respuesta': { bg: 'bg-blue-50',      text: 'text-blue-500',    border: 'border-blue-200',    dot: 'bg-blue-300',    pulse: false },
  'Email enviado pendiente de respuesta': { bg: 'bg-blue-50',   text: 'text-blue-500',    border: 'border-blue-200',    dot: 'bg-blue-300',    pulse: false },
  'Contactado':                     { bg: 'bg-[#00A859]/10',    text: 'text-[#00A859]',   border: 'border-[#00A859]/30',dot: 'bg-[#00A859]',   pulse: false },
  'Contactado ':                    { bg: 'bg-[#00A859]/10',    text: 'text-[#00A859]',   border: 'border-[#00A859]/30',dot: 'bg-[#00A859]',   pulse: false },
  'Volver a llamar':                { bg: 'bg-blue-50',         text: 'text-blue-600',    border: 'border-blue-200',    dot: 'bg-blue-400',    pulse: false },
  'Email enviado pendiente respuesta ': { bg: 'bg-blue-50',     text: 'text-blue-500',    border: 'border-blue-200',    dot: 'bg-blue-300',    pulse: false },
}

function estadoBadge(estado) {
  return ESTADO_BADGE[estado] ?? { bg: 'bg-gray-100', text: 'text-gray-500', border: 'border-gray-200', dot: 'bg-gray-300', pulse: false }
}

// ═══════════════════════════════════════════════════════════
//  ACORDEÓN
// ═══════════════════════════════════════════════════════════
function toggleCentro(centro) {
  centrosExpandidos.value.has(centro)
    ? centrosExpandidos.value.delete(centro)
    : centrosExpandidos.value.add(centro)
}

function familiaKey(centro, familia) { return `${centro}||${familia}` }

function toggleFamilia(centro, familia) {
  const k = familiaKey(centro, familia)
  familiasExpandidas.value.has(k)
    ? familiasExpandidas.value.delete(k)
    : familiasExpandidas.value.add(k)
}

function toggleEmpresa(id) {
  empresaExpandida.value = empresaExpandida.value === id ? null : id
}

// ═══════════════════════════════════════════════════════════
//  FLUJO EDITAR
// ═══════════════════════════════════════════════════════════
function pedirEdicion(empresa) {
  empresaEditTemp.value    = empresa
  mostrarConfirmEdit.value = true
}

function confirmarEdicion() {
  mostrarConfirmEdit.value = false
  empresaAEditar.value     = { ...empresaEditTemp.value }
  mostrarEditarEmpresa.value = true
}

function onEmpresaCreada(empresa) {
  mostrarSnack(`"${empresa.nombre_comercial}" creada correctamente.`, 'ok')
  cargarDatos()
}

function onEmpresaActualizada(empresaActualizada) {
  mostrarSnack(`"${empresaActualizada.nombre_comercial}" actualizada correctamente.`, 'ok')
  cargarDatos()
}

// ═══════════════════════════════════════════════════════════
//  FLUJO ELIMINAR
// ═══════════════════════════════════════════════════════════
function pedirEliminacion(empresa) {
  empresaParaEliminar.value    = empresa
  mostrarEliminarEmpresa.value = true
}

function onEmpresaEliminada(data) {
  mostrarEliminarEmpresa.value = false
  mostrarSnack(`"${data.nombre}" eliminada correctamente.`, 'ok')
  cargarDatos()
}


</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] p-4 md:p-10 font-sans text-[#1F2937] overflow-x-hidden">

    <!-- ══════════════ AVISO EXPIRACIÓN DE SESIÓN ═══════════ -->
    <Transition name="modal-fade">
      <div
        v-if="mostrarAvisoToken"
        class="max-w-7xl mx-auto mb-4 flex items-center gap-3
               px-5 py-3 rounded-2xl border
               bg-amber-50 border-amber-300 text-amber-800"
      >
        <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm font-semibold flex-1">
          Tu sesión expira en
          <span class="font-black">{{ minutosRestantes }} minuto{{ minutosRestantes !== 1 ? 's' : '' }}</span>.
          Guarda tu trabajo y vuelve a iniciar sesión para no perder el acceso.
        </p>
        <button
          @click="authStore.logout(); router.push('/')"
          class="text-xs font-black uppercase tracking-widest px-3 py-1.5 rounded-xl
                 bg-amber-500 text-white hover:bg-amber-600 transition-all shrink-0"
        >
          Renovar sesión
        </button>
      </div>
    </Transition>

    <!-- ══════════════════════ CABECERA ══════════════════════ -->
    <div class="max-w-7xl mx-auto">
      <header class="mb-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
          <div>
            <div class="inline-flex items-center gap-2 mb-2 px-3 py-1 rounded-full
                        bg-[#00A859]/10 border border-[#00A859]/20">
              <span class="w-2 h-2 rounded-full bg-[#00A859]" />
              <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">Base de datos</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black tracking-tight text-[#121212]">
              Empresas <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00A859] to-[#99CC33]">DuaLab</span>
            </h1>
            <p class="text-gray-500 text-sm mt-1">Consulta, edita y gestiona todas las empresas registradas.</p>
          </div>

          <!-- Acciones de cabecera -->
          <div class="flex flex-wrap items-center gap-2">
            <!-- Botón catálogo FP — low-visibility -->
            <div class="relative group/cat-tip">
              <button
                @click="pedirAbrirCatalogo"
                :disabled="cargando"
                class="flex items-center gap-1.5 px-3.5 py-2.5 rounded-2xl font-bold text-xs
                       bg-white text-gray-400 border border-gray-200
                       hover:text-[#1F2937] hover:border-gray-300 hover:bg-gray-50
                       transition-all duration-200
                       disabled:opacity-40 disabled:cursor-not-allowed"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span class="hidden sm:inline">Catálogo FP</span>
              </button>
              <div class="pointer-events-none absolute right-0 top-full mt-2 z-20
                          w-max max-w-[200px] px-3 py-2 rounded-xl
                          bg-[#1F2937] text-white text-[11px] font-semibold leading-snug
                          shadow-lg opacity-0 group-hover/cat-tip:opacity-100
                          translate-y-1 group-hover/cat-tip:translate-y-0
                          transition-all duration-150">
                Gestionar familias y ciclos del catálogo
                <div class="absolute -top-1 right-3 w-2 h-2 bg-[#1F2937] rotate-45"></div>
              </div>
            </div>

            <!-- Botón nuevo centro educativo -->
            <button
              @click="mostrarNuevoCentro = true"
              :disabled="cargando"
              class="flex items-center gap-2 px-5 py-2.5 rounded-2xl font-black text-sm
                     bg-[#1F2937] text-white border border-[#333333]
                     hover:bg-[#2d3748] transition-all duration-200 shadow-sm
                     disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="w-4 h-4 text-[#99CC33]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055"/>
              </svg>
              Nuevo centro
            </button>

            <!-- Botón nueva empresa -->
            <button
              @click="mostrarNuevaEmpresa = true"
              :disabled="cargando"
              class="flex items-center gap-2 px-5 py-2.5 rounded-2xl font-black text-sm
                     bg-[#00A859] text-white
                     hover:bg-[#009950] transition-all duration-200 shadow-sm
                     disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                  d="M12 4v16m8-8H4"/>
              </svg>
              Nueva empresa
            </button>

            <!-- Botón recargar -->
            <button
              @click="cargarDatos"
              :disabled="cargando"
              class="flex items-center gap-2 px-5 py-2.5 rounded-2xl font-bold text-sm
                     bg-[#1F2937] text-white border border-[#333333]
                     hover:bg-[#2d3748] transition-all duration-200
                     disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="w-4 h-4" :class="cargando ? 'animate-spin' : ''"
                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              {{ cargando ? 'Cargando...' : 'Actualizar' }}
            </button>
          </div>
        </div>

        <!-- Stats chips -->
        <div class="flex flex-wrap gap-3" v-if="!cargando && !errorCarga">
          <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-2xl border border-gray-100 shadow-sm">
            <svg class="w-4 h-4 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
            </svg>
            <span class="font-black text-xl text-[#1F2937]">{{ totalEmpresas }}</span>
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">empresas</span>
          </div>
          <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-2xl border border-gray-100 shadow-sm">
            <svg class="w-4 h-4 text-[#99CC33]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
            </svg>
            <span class="font-black text-xl text-[#1F2937]">{{ totalCentros }}</span>
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">centros</span>
          </div>
          <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-2xl border border-gray-100 shadow-sm">
            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            <span class="font-black text-xl text-[#1F2937]">{{ todasLasFamilias.length }}</span>
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">familias</span>
          </div>
          <div v-if="busqueda || filtroFamilia || filtroCentro"
               class="flex items-center gap-2 px-4 py-2 bg-amber-50 rounded-2xl border border-amber-200 shadow-sm">
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            <span class="font-black text-xl text-amber-700">{{ totalFiltradas }}</span>
            <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider">mostrando</span>
          </div>
        </div>
      </header>

      <!-- ══════════════════ FILTROS ══════════════════ -->
      <div v-if="!cargando && !errorCarga"
           class="bg-white rounded-[1.5rem] border border-gray-100 shadow-sm p-5 mb-6
                  flex flex-col md:flex-row gap-3">
        <!-- Buscador texto -->
        <div class="relative flex-1">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
               fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input
            v-model="busqueda"
            type="text"
            placeholder="Buscar por nombre, CIF, municipio o contacto..."
            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-200
                   text-sm focus:outline-none focus:ring-2 focus:ring-[#00A859]/30
                   focus:border-[#00A859] transition-all"
          />
          <button v-if="busqueda" @click="busqueda = ''"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Filtro centro -->
        <select
          v-model="filtroCentro"
          class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm
                 focus:outline-none focus:ring-2 focus:ring-[#00A859]/30 focus:border-[#00A859]
                 bg-white text-gray-700 w-full md:w-auto md:min-w-[160px]"
        >
          <option value="">Todos los centros</option>
          <option v-for="c in todosCentros" :key="c" :value="c">{{ c }}</option>
        </select>

        <!-- Filtro familia -->
        <select
          v-model="filtroFamilia"
          class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm
                 focus:outline-none focus:ring-2 focus:ring-[#00A859]/30 focus:border-[#00A859]
                 bg-white text-gray-700 w-full md:w-auto md:min-w-[160px]"
        >
          <option value="">Todas las familias</option>
          <option v-for="f in todasLasFamilias" :key="f" :value="f">{{ f }}</option>
        </select>

        <!-- Limpiar filtros -->
        <button
          v-if="busqueda || filtroFamilia || filtroCentro"
          @click="busqueda = ''; filtroFamilia = ''; filtroCentro = ''"
          class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl border border-red-200
                 bg-red-50 text-red-500 text-sm font-bold hover:bg-red-100 transition-all shrink-0"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
          Limpiar
        </button>
      </div>

      <!-- ══════════════ ESTADO CARGANDO ══════════════ -->
      <div v-if="cargando" class="flex flex-col items-center justify-center py-24 gap-4">
        <div class="w-12 h-12 rounded-full border-4 border-[#00A859]/20 border-t-[#00A859] animate-spin" />
        <p class="text-gray-500 font-medium">Cargando base de datos...</p>
      </div>

      <!-- ══════════════ ESTADO ERROR ══════════════════ -->
      <div v-else-if="errorCarga"
           class="bg-red-50 border border-red-200 rounded-[1.5rem] p-8 text-center">
        <svg class="w-10 h-10 text-red-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-red-700 font-bold mb-2">Error al cargar</p>
        <p class="text-red-500 text-sm mb-4">{{ errorCarga }}</p>
        <button @click="cargarDatos"
                class="px-5 py-2 bg-red-500 text-white rounded-xl font-bold text-sm hover:bg-red-600 transition-all">
          Reintentar
        </button>
      </div>

      <!-- ══════════════ SIN RESULTADOS ════════════════ -->
      <div v-else-if="centrosOrdenados.length === 0"
           class="bg-white border border-gray-100 rounded-[1.5rem] p-12 text-center shadow-sm">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <p class="text-gray-400 font-bold text-lg">No se encontraron empresas</p>
        <p class="text-gray-400 text-sm mt-1">Prueba a cambiar los filtros de búsqueda.</p>
      </div>

      <!-- ══════════════ ACORDEÓN PRINCIPAL ═══════════ -->
      <div v-else class="space-y-4">
        <div
          v-for="centro in centrosOrdenados"
          :key="centro"
          class="bg-white rounded-[1.75rem] border border-gray-100 shadow-sm overflow-hidden"
        >
          <!-- Cabecera del centro -->
          <div class="flex items-center pr-3 gap-1">
            <button
              @click="toggleCentro(centro)"
              class="flex-1 flex items-center gap-4 px-6 py-5
                     hover:bg-gray-50/80 transition-colors duration-150 text-left min-w-0"
            >
              <div class="w-10 h-10 rounded-2xl bg-[#1F2937] flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-[#99CC33]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <h2 class="font-black text-lg text-[#1F2937] truncate">{{ centro }}</h2>
                <p class="text-xs text-gray-400 font-medium mt-0.5">
                  {{ Object.values(datosPorCentro[centro].familias).reduce((n,f) => n + f.empresas.length, 0) }}
                  {{ Object.values(datosPorCentro[centro].familias).reduce((n,f) => n + f.empresas.length, 0) === 1 ? 'empresa' : 'empresas' }}
                  ·
                  {{ Object.keys(datosPorCentro[centro].familias).length }}
                  {{ Object.keys(datosPorCentro[centro].familias).length === 1 ? 'familia' : 'familias' }}
                  ·
                  {{ Object.values(datosPorCentro[centro].familias).reduce((n,f) => n + f.ciclos.length, 0) }}
                  ciclos
                </p>
              </div>
              <svg
                class="w-5 h-5 text-gray-400 transition-transform duration-300 shrink-0"
                :class="centrosExpandidos.has(centro) ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </button>

            <!-- Acciones del centro (solo centros del catálogo con id real) -->
            <template v-if="datosPorCentro[centro]?.id">
              <!-- Botón editar -->
              <div class="relative group/edit-tip shrink-0">
                <button
                  @click.stop="pedirEditarCentro(centro)"
                  class="w-9 h-9 rounded-xl flex items-center justify-center
                         text-gray-300 hover:text-[#00A859] hover:bg-[#00A859]/10
                         border border-transparent hover:border-[#00A859]/30
                         transition-all duration-150"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                  </svg>
                </button>
                <!-- Tooltip -->
                <div class="pointer-events-none absolute right-0 top-full mt-2 z-20
                            w-max max-w-[180px] px-3 py-2 rounded-xl
                            bg-[#1F2937] text-white text-[11px] font-semibold leading-snug
                            shadow-lg opacity-0 group-hover/edit-tip:opacity-100
                            translate-y-1 group-hover/edit-tip:translate-y-0
                            transition-all duration-150">
                  Editar centro, familias y ciclos
                  <div class="absolute -top-1 right-3 w-2 h-2 bg-[#1F2937] rotate-45"></div>
                </div>
              </div>
              <!-- Botón eliminar -->
              <button
                @click.stop="pedirEliminarCentro(centro)"
                title="Eliminar centro educativo"
                class="shrink-0 w-9 h-9 rounded-xl flex items-center justify-center
                       text-gray-300 hover:text-red-500 hover:bg-red-50
                       border border-transparent hover:border-red-200
                       transition-all duration-150"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </template>
          </div>

          <!-- Familias del centro -->
          <div v-if="centrosExpandidos.has(centro)" class="border-t border-gray-100">
            <div
              v-for="(familiaData, familia) in datosPorCentro[centro].familias"
              :key="familia"
              class="border-b border-gray-50 last:border-b-0"
            >
              <!-- Cabecera de la familia -->
              <button
                @click="toggleFamilia(centro, familia)"
                class="w-full flex items-center gap-3 pl-4 sm:pl-8 pr-4 sm:pr-6 py-3.5
                       hover:bg-gray-50/60 transition-colors duration-150 text-left"
              >
                <div class="w-2 h-2 rounded-full bg-[#00A859] shrink-0" />
                <span class="flex-1 font-bold text-sm text-gray-700 truncate">{{ familia }}</span>
                <!-- Badge ciclos -->
                <span v-if="familiaData.ciclos.length"
                  class="text-[10px] font-black uppercase tracking-widest
                         bg-[#1F2937]/8 text-gray-500 px-2 py-0.5 rounded-full shrink-0">
                  {{ familiaData.ciclos.length }} ciclos
                </span>
                <!-- Badge empresas -->
                <span v-if="familiaData.empresas.length"
                  class="text-[10px] font-black uppercase tracking-widest
                         bg-[#00A859]/10 text-[#00A859] px-2.5 py-1 rounded-full shrink-0">
                  {{ familiaData.empresas.length }}
                </span>
                <svg
                  class="w-4 h-4 text-gray-400 transition-transform duration-200 ml-1 shrink-0"
                  :class="familiasExpandidas.has(familiaKey(centro, familia)) ? 'rotate-180' : ''"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>

              <!-- Contenido expandido: ciclos + empresas -->
              <div v-if="familiasExpandidas.has(familiaKey(centro, familia))">

                <!-- Lista de ciclos formativos del centro -->
                <div v-if="familiaData.ciclos.length"
                  class="pl-4 sm:pl-12 pr-4 sm:pr-6 py-3 border-t border-gray-50 bg-gray-50/40">
                  <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2 flex items-center gap-1.5">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Ciclos formativos que imparte
                  </p>
                  <div class="flex flex-wrap gap-1.5">
                    <span
                      v-for="ciclo in familiaData.ciclos" :key="ciclo.id"
                      class="text-[11px] font-semibold bg-white border border-gray-200
                             text-gray-600 px-2.5 py-1 rounded-full"
                    >
                      {{ ciclo.nombre }}
                    </span>
                  </div>
                </div>

                <!-- Lista de empresas -->
                <div
                  v-for="empresa in familiaData.empresas"
                  :key="empresa.id"
                  class="border-t border-gray-50"
                >
                  <!-- Fila empresa (summary) -->
                  <div class="flex items-center gap-2 sm:gap-3 pl-4 sm:pl-12 pr-3 sm:pr-4 py-3.5 hover:bg-gray-50/50 transition-colors">
                    <!-- Info rápida -->
                    <button
                      @click="toggleEmpresa(empresa.id)"
                      class="flex-1 flex items-center gap-4 min-w-0 text-left"
                    >
                      <div class="w-8 h-8 rounded-xl bg-[#00A859]/10 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1v1H9V7zm5 0h1v1h-1V7zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1z"/>
                        </svg>
                      </div>
                      <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                          <span class="font-bold text-sm text-[#1F2937] truncate">
                            {{ empresa.nombre_comercial }}
                          </span>
                          <span v-if="empresa.sector"
                                class="text-[10px] font-bold uppercase tracking-wider
                                       bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full shrink-0">
                            {{ empresa.sector }}
                          </span>
                          <span v-if="empresa.tamano"
                                class="text-[10px] font-bold uppercase tracking-wider
                                       bg-blue-50 text-blue-500 px-2 py-0.5 rounded-full shrink-0">
                            {{ empresa.tamano }}
                          </span>
                          <span v-if="empresa.estado_contacto"
                            :class="[estadoBadge(empresa.estado_contacto).bg, estadoBadge(empresa.estado_contacto).text, estadoBadge(empresa.estado_contacto).border]"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest border shrink-0">
                            <span :class="[estadoBadge(empresa.estado_contacto).dot, estadoBadge(empresa.estado_contacto).pulse ? 'animate-pulse' : '']"
                              class="w-1.5 h-1.5 rounded-full"></span>
                            {{ empresa.estado_contacto }}
                          </span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5 truncate">
                          <span v-if="empresa.municipio">{{ empresa.municipio }}</span>
                          <span v-if="empresa.municipio && empresa.persona_contacto"> · </span>
                          <span v-if="empresa.persona_contacto">{{ empresa.persona_contacto }}</span>
                          <span v-if="!empresa.municipio && !empresa.persona_contacto" class="italic">Sin datos de contacto</span>
                        </p>
                      </div>
                      <svg
                        class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0"
                        :class="empresaExpandida === empresa.id ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                      </svg>
                    </button>

                    <!-- Acciones -->
                    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                      <button
                        @click="pedirEdicion(empresa)"
                        title="Modificar empresa"
                        class="flex items-center gap-1.5 px-2 sm:px-3 py-1.5 rounded-xl
                               bg-[#00A859]/10 border border-[#00A859]/20 text-[#00A859]
                               hover:bg-[#00A859]/20 hover:border-[#00A859]/40
                               font-bold text-xs transition-all duration-150"
                      >
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span class="hidden sm:inline">Editar</span>
                      </button>
                      <button
                        @click="pedirEliminacion(empresa)"
                        title="Eliminar empresa"
                        class="flex items-center gap-1.5 px-2 sm:px-3 py-1.5 rounded-xl
                               bg-red-50 border border-red-200 text-red-500
                               hover:bg-red-100 hover:border-red-300
                               font-bold text-xs transition-all duration-150"
                      >
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span class="hidden sm:inline">Eliminar</span>
                      </button>
                    </div>
                  </div>

                  <!-- Panel de detalle expandido -->
                  <Transition name="expand">
                    <div
                      v-if="empresaExpandida === empresa.id"
                      class="pl-3 sm:pl-12 pr-3 sm:pr-6 pb-5 pt-3 bg-gray-50/60 border-t border-gray-100"
                    >
                      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">

                        <!-- Datos básicos -->
                        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                          <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/>
                            </svg>
                            Datos básicos
                          </h4>
                          <dl class="space-y-2 text-sm">
                            <div v-if="empresa.razon_social" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Razón social</dt>
                              <dd class="font-medium text-gray-700 break-words min-w-0">{{ empresa.razon_social }}</dd>
                            </div>
                            <div v-if="empresa.cif" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">CIF</dt>
                              <dd class="font-medium text-gray-700">{{ empresa.cif }}</dd>
                            </div>
                            <div v-if="empresa.sector" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Sector</dt>
                              <dd class="font-medium text-gray-700">{{ empresa.sector }}</dd>
                            </div>
                            <div v-if="empresa.actividad" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Actividad</dt>
                              <dd class="font-medium text-gray-700 break-words min-w-0">{{ empresa.actividad }}</dd>
                            </div>
                            <div v-if="empresa.tamano" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Tamaño</dt>
                              <dd class="font-medium text-gray-700">{{ empresa.tamano }}</dd>
                            </div>
                            <div v-if="empresa.web" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Web</dt>
                              <dd class="font-medium text-[#00A859] break-all min-w-0">{{ empresa.web }}</dd>
                            </div>
                            <div v-if="empresa.familias_nombres?.length" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Familias</dt>
                              <dd class="flex flex-wrap gap-1 min-w-0">
                                <span
                                  v-for="f in empresa.familias_nombres" :key="f"
                                  class="text-[10px] font-bold bg-[#00A859]/10 text-[#00A859] px-2 py-0.5 rounded-full"
                                >{{ f }}</span>
                              </dd>
                            </div>
                            <p v-if="!empresa.razon_social && !empresa.cif && !empresa.sector && !empresa.tamano"
                               class="text-gray-300 text-xs italic">Sin datos básicos registrados</p>
                          </dl>
                        </div>

                        <!-- Contacto -->
                        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                          <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Contacto
                          </h4>
                          <dl class="space-y-2 text-sm">
                            <div v-if="empresa.persona_contacto" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Persona</dt>
                              <dd class="font-medium text-gray-700">{{ empresa.persona_contacto }}</dd>
                            </div>
                            <div v-if="empresa.posicion_contacto" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Cargo</dt>
                              <dd class="font-medium text-gray-700">{{ empresa.posicion_contacto }}</dd>
                            </div>
                            <div v-if="empresa.telefono" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Teléfono</dt>
                              <dd class="font-medium text-gray-700">{{ empresa.telefono }}</dd>
                            </div>
                            <div v-if="empresa.email_general" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Email</dt>
                              <dd class="font-medium text-[#00A859] break-all min-w-0">{{ empresa.email_general }}</dd>
                            </div>
                            <div v-if="empresa.email_contacto" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Email ctc.</dt>
                              <dd class="font-medium text-[#00A859] break-all min-w-0">{{ empresa.email_contacto }}</dd>
                            </div>
                            <div v-if="empresa.horario_atencion" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Horario</dt>
                              <dd class="font-medium text-gray-700">{{ empresa.horario_atencion }}</dd>
                            </div>
                            <div v-if="empresa.estado_contacto" class="flex gap-2 items-start">
                              <dt class="text-gray-400 shrink-0 w-24 pt-0.5">Estado</dt>
                              <dd>
                                <span :class="[estadoBadge(empresa.estado_contacto).bg, estadoBadge(empresa.estado_contacto).text, estadoBadge(empresa.estado_contacto).border]"
                                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border">
                                  <span :class="[estadoBadge(empresa.estado_contacto).dot, estadoBadge(empresa.estado_contacto).pulse ? 'animate-pulse' : '']"
                                    class="w-1.5 h-1.5 rounded-full shrink-0"></span>
                                  {{ empresa.estado_contacto }}
                                </span>
                              </dd>
                            </div>
                            <div v-if="empresa.fecha_cita" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Cita</dt>
                              <dd class="font-medium text-gray-700">{{ empresa.fecha_cita }}</dd>
                            </div>
                            <p v-if="!empresa.persona_contacto && !empresa.telefono && !empresa.email_general"
                               class="text-gray-300 text-xs italic">Sin datos de contacto registrados</p>
                          </dl>
                        </div>

                        <!-- Ubicación -->
                        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm">
                          <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Ubicación
                          </h4>
                          <dl class="space-y-2 text-sm">
                            <div v-if="empresa.direccion" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Dirección</dt>
                              <dd class="font-medium text-gray-700 break-words min-w-0">
                                {{ empresa.direccion }}
                                {{ empresa.numero ? empresa.numero : '' }}
                                {{ empresa.otros_direccion ? empresa.otros_direccion : '' }}
                              </dd>
                            </div>
                            <div v-if="empresa.codigo_postal" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">C.P.</dt>
                              <dd class="font-medium text-gray-700">{{ empresa.codigo_postal }}</dd>
                            </div>
                            <div v-if="empresa.municipio" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Municipio</dt>
                              <dd class="font-medium text-gray-700">{{ empresa.municipio }}</dd>
                            </div>
                            <div v-if="empresa.provincia" class="flex gap-2">
                              <dt class="text-gray-400 shrink-0 w-24">Provincia</dt>
                              <dd class="font-medium text-gray-700">{{ empresa.provincia }}</dd>
                            </div>
                            <p v-if="!empresa.direccion && !empresa.municipio && !empresa.provincia"
                               class="text-gray-300 text-xs italic">Sin datos de ubicación registrados</p>
                          </dl>
                        </div>

                        <!-- Datos B2B (si existen) -->
                        <div
                          v-if="empresa.dia_a_normal || empresa.friccion_area || empresa.friccion_problema || empresa.consecuencias || empresa.restricciones || empresa.lo_que_no_quieren"
                          class="bg-white rounded-2xl border border-[#00A859]/20 p-4 shadow-sm sm:col-span-2 xl:col-span-3"
                        >
                          <h4 class="text-[10px] font-black uppercase tracking-widest text-[#00A859] mb-3 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            Datos B2B / Diagnóstico
                          </h4>
                          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div v-if="empresa.dia_a_normal">
                              <p class="text-[10px] font-black uppercase tracking-wider text-gray-400 mb-1">Día a día normal</p>
                              <p class="text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-3">{{ empresa.dia_a_normal }}</p>
                            </div>
                            <div v-if="empresa.friccion_area">
                              <p class="text-[10px] font-black uppercase tracking-wider text-gray-400 mb-1">Área de fricción</p>
                              <p class="text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-3">{{ empresa.friccion_area }}</p>
                            </div>
                            <div v-if="empresa.friccion_problema" class="sm:col-span-2">
                              <p class="text-[10px] font-black uppercase tracking-wider text-gray-400 mb-1">El problema</p>
                              <p class="text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-3">{{ empresa.friccion_problema }}</p>
                            </div>
                            <div v-if="empresa.consecuencias">
                              <p class="text-[10px] font-black uppercase tracking-wider text-gray-400 mb-1">Consecuencias</p>
                              <p class="text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-3">{{ empresa.consecuencias }}</p>
                            </div>
                            <div v-if="empresa.restricciones">
                              <p class="text-[10px] font-black uppercase tracking-wider text-gray-400 mb-1">Restricciones</p>
                              <p class="text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-3">{{ empresa.restricciones }}</p>
                            </div>
                            <div v-if="empresa.lo_que_no_quieren" class="sm:col-span-2">
                              <p class="text-[10px] font-black uppercase tracking-wider text-gray-400 mb-1">Lo que no quieren</p>
                              <p class="text-gray-700 leading-relaxed bg-gray-50 rounded-xl p-3">{{ empresa.lo_que_no_quieren }}</p>
                            </div>
                          </div>
                        </div>

                      </div>
                    </div>
                  </Transition>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══════════════ EMPRESAS HUÉRFANAS ══════════════════ -->
      <Transition name="expand">
        <div
          v-if="!cargando && !errorCarga && empresasHuerfanas.length > 0"
          class="mt-6 rounded-[1.75rem] border border-amber-200 bg-amber-50/60 overflow-hidden"
        >
          <!-- Cabecera sección -->
          <div class="flex items-center gap-4 px-6 py-5 border-b border-amber-200/70">
            <div class="w-10 h-10 rounded-2xl bg-amber-100 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <h2 class="font-black text-lg text-amber-800">Empresas sin centro asignado</h2>
              <p class="text-xs text-amber-600 font-medium mt-0.5">
                {{ empresasHuerfanas.length }}
                {{ empresasHuerfanas.length === 1 ? 'empresa' : 'empresas' }}
                sin centro educativo vinculado · Edítalas para reasignarlas
              </p>
            </div>
          </div>

          <!-- Lista de empresas huérfanas -->
          <div>
            <div
              v-for="empresa in empresasHuerfanas"
              :key="empresa.id"
              class="border-b border-amber-100 last:border-b-0"
            >
              <!-- Fila empresa -->
              <div class="flex items-center gap-2 sm:gap-3 pl-4 sm:pl-6 pr-3 sm:pr-4 py-3.5
                          hover:bg-amber-100/50 transition-colors">
                <button
                  @click="toggleEmpresa(empresa.id)"
                  class="flex-1 flex items-center gap-4 min-w-0 text-left"
                >
                  <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1v1H9V7zm5 0h1v1h-1V7zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1zm-5 4h1v1H9v-1zm5 0h1v1h-1v-1z"/>
                    </svg>
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                      <span class="font-bold text-sm text-amber-900 truncate">
                        {{ empresa.nombre_comercial }}
                      </span>
                      <span v-if="empresa.sector"
                            class="text-[10px] font-bold uppercase tracking-wider
                                   bg-amber-100 text-amber-600 px-2 py-0.5 rounded-full shrink-0">
                        {{ empresa.sector }}
                      </span>
                      <span v-if="empresa.estado_contacto"
                        :class="[estadoBadge(empresa.estado_contacto).bg, estadoBadge(empresa.estado_contacto).text, estadoBadge(empresa.estado_contacto).border]"
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest border shrink-0">
                        <span :class="[estadoBadge(empresa.estado_contacto).dot, estadoBadge(empresa.estado_contacto).pulse ? 'animate-pulse' : '']"
                          class="w-1.5 h-1.5 rounded-full"></span>
                        {{ empresa.estado_contacto }}
                      </span>
                    </div>
                    <p class="text-xs text-amber-500 mt-0.5 truncate">
                      <span v-if="empresa.municipio">{{ empresa.municipio }}</span>
                      <span v-if="empresa.municipio && empresa.persona_contacto"> · </span>
                      <span v-if="empresa.persona_contacto">{{ empresa.persona_contacto }}</span>
                      <span v-if="!empresa.municipio && !empresa.persona_contacto" class="italic">Sin datos de contacto</span>
                    </p>
                  </div>
                  <svg
                    class="w-4 h-4 text-amber-400 transition-transform duration-200 shrink-0"
                    :class="empresaExpandida === empresa.id ? 'rotate-180' : ''"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                  </svg>
                </button>

                <!-- Acciones -->
                <div class="flex items-center gap-1.5 shrink-0">
                  <button
                    @click="pedirEdicion(empresa)"
                    title="Editar y reasignar centro"
                    class="flex items-center gap-1.5 px-2 sm:px-3 py-1.5 rounded-xl
                           bg-amber-100 border border-amber-300 text-amber-700
                           hover:bg-amber-200 hover:border-amber-400
                           font-bold text-xs transition-all duration-150"
                  >
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span class="hidden sm:inline">Editar</span>
                  </button>
                  <button
                    @click="pedirEliminacion(empresa)"
                    title="Eliminar empresa"
                    class="flex items-center gap-1.5 px-2 sm:px-3 py-1.5 rounded-xl
                           bg-red-50 border border-red-200 text-red-500
                           hover:bg-red-100 hover:border-red-300
                           font-bold text-xs transition-all duration-150"
                  >
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span class="hidden sm:inline">Eliminar</span>
                  </button>
                </div>
              </div>

              <!-- Panel de detalle expandido (mismo que en el acordeón) -->
              <Transition name="expand">
                <div
                  v-if="empresaExpandida === empresa.id"
                  class="pl-3 sm:pl-6 pr-3 sm:pr-6 pb-5 pt-3 bg-amber-50/80 border-t border-amber-100"
                >
                  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">
                    <div class="bg-white rounded-2xl border border-amber-100 p-4 shadow-sm">
                      <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3">Datos básicos</h4>
                      <dl class="space-y-2 text-sm">
                        <div v-if="empresa.sector" class="flex gap-2">
                          <dt class="text-gray-400 shrink-0 w-24">Sector</dt>
                          <dd class="font-medium text-gray-700">{{ empresa.sector }}</dd>
                        </div>
                        <div v-if="empresa.tamano" class="flex gap-2">
                          <dt class="text-gray-400 shrink-0 w-24">Tamaño</dt>
                          <dd class="font-medium text-gray-700">{{ empresa.tamano }}</dd>
                        </div>
                        <div v-if="empresa.web" class="flex gap-2">
                          <dt class="text-gray-400 shrink-0 w-24">Web</dt>
                          <dd class="font-medium text-[#00A859] break-all min-w-0">{{ empresa.web }}</dd>
                        </div>
                        <div v-if="empresa.familias_nombres?.length" class="flex gap-2">
                          <dt class="text-gray-400 shrink-0 w-24">Familias</dt>
                          <dd class="flex flex-wrap gap-1 min-w-0">
                            <span
                              v-for="f in empresa.familias_nombres" :key="f"
                              class="text-[10px] font-bold bg-[#00A859]/10 text-[#00A859] px-2 py-0.5 rounded-full"
                            >{{ f }}</span>
                          </dd>
                        </div>
                        <p v-if="!empresa.sector && !empresa.tamano && !empresa.familias_nombres?.length"
                           class="text-gray-300 text-xs italic">Sin datos básicos registrados</p>
                      </dl>
                    </div>
                    <div class="bg-white rounded-2xl border border-amber-100 p-4 shadow-sm">
                      <h4 class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3">Contacto</h4>
                      <dl class="space-y-2 text-sm">
                        <div v-if="empresa.persona_contacto" class="flex gap-2">
                          <dt class="text-gray-400 shrink-0 w-24">Persona</dt>
                          <dd class="font-medium text-gray-700">{{ empresa.persona_contacto }}</dd>
                        </div>
                        <div v-if="empresa.telefono" class="flex gap-2">
                          <dt class="text-gray-400 shrink-0 w-24">Teléfono</dt>
                          <dd class="font-medium text-gray-700">{{ empresa.telefono }}</dd>
                        </div>
                        <div v-if="empresa.email_general" class="flex gap-2">
                          <dt class="text-gray-400 shrink-0 w-24">Email</dt>
                          <dd class="font-medium text-[#00A859] break-all min-w-0">{{ empresa.email_general }}</dd>
                        </div>
                        <p v-if="!empresa.persona_contacto && !empresa.telefono && !empresa.email_general"
                           class="text-gray-300 text-xs italic">Sin datos de contacto registrados</p>
                      </dl>
                    </div>
                  </div>
                </div>
              </Transition>
            </div>
          </div>
        </div>
      </Transition>

    </div>

    <!-- ════════════════ MODAL: CONFIRMAR EDICIÓN ════════════════ -->
    <Transition name="modal-fade">
      <div v-if="mostrarConfirmEdit"
           class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-md w-full p-7 border border-gray-100">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-2xl bg-amber-100 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
            </div>
            <div>
              <h3 class="font-black text-lg text-[#1F2937]">Modificar empresa</h3>
              <p class="text-xs text-gray-400">Vas a editar los datos de esta empresa</p>
            </div>
          </div>
          <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-5">
            <p class="text-sm font-bold text-amber-800 mb-1">
              "{{ empresaEditTemp?.nombre_comercial }}"
            </p>
            <p class="text-xs text-amber-600">
              Los cambios que guardes sobreescribirán los datos actuales en la base de datos.
              Asegúrate de que la información es correcta antes de guardar.
            </p>
          </div>
          <div class="flex gap-3">
            <button
              @click="mostrarConfirmEdit = false"
              class="flex-1 py-2.5 rounded-xl border border-gray-200
                     text-sm font-bold text-gray-500 hover:bg-gray-50 transition-all"
            >
              Cancelar
            </button>
            <button
              @click="confirmarEdicion"
              class="flex-1 py-2.5 rounded-xl
                     bg-[#00A859] text-white text-sm font-black
                     hover:bg-[#009950] transition-all shadow-sm"
            >
              Abrir editor
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ════════════ MODAL: CONFIRMAR APERTURA CATÁLOGO FP ═══ -->
    <Transition name="modal-fade">
      <div v-if="mostrarConfirmCatalogo"
           class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-[2rem] shadow-2xl max-w-md w-full p-7 border border-gray-100">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-11 h-11 rounded-2xl bg-[#1F2937]/8 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-[#1F2937]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
            </div>
            <div>
              <h3 class="font-black text-lg text-[#1F2937]">Editar catálogo de FP</h3>
              <p class="text-xs text-gray-400">Familias profesionales y ciclos formativos</p>
            </div>
          </div>
          <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-5">
            <p class="text-xs text-amber-700 font-semibold leading-relaxed">
              Estás accediendo a la edición del catálogo académico. Los cambios en familias y ciclos
              afectan a <span class="font-black">todo el sistema</span>: centros, empresas y generación de microretos.
              Procede solo si estás seguro.
            </p>
          </div>
          <div class="flex gap-3">
            <button
              @click="mostrarConfirmCatalogo = false"
              class="flex-1 py-2.5 rounded-xl border border-gray-200
                     text-sm font-bold text-gray-500 hover:bg-gray-50 transition-all"
            >
              Cancelar
            </button>
            <button
              @click="confirmarAbrirCatalogo"
              class="flex-1 py-2.5 rounded-xl
                     bg-[#1F2937] text-white text-sm font-black
                     hover:bg-[#374151] transition-all shadow-sm"
            >
              Abrir catálogo
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ════════════ MODAL: CATÁLOGO FAMILIAS Y CICLOS ═══════ -->
    <GestionFamiliasCiclosModal
      :visible="mostrarCatalogo"
      @cerrar="mostrarCatalogo = false"
      @cambios="cargarDatos"
    />

    <!-- ════════════ MODAL: ELIMINAR EMPRESA ══════════════════ -->
    <EliminarEmpresaModal
      :visible="mostrarEliminarEmpresa"
      :empresa="empresaParaEliminar"
      @empresa-eliminada="onEmpresaEliminada"
      @cerrar="mostrarEliminarEmpresa = false"
    />

    <!-- ════════════ MODAL: ELIMINAR CENTRO ════════════════════ -->
    <EliminarCentroModal
      :visible="mostrarEliminarCentro"
      :centro="centroAEliminar"
      :num-empresas="centroAEliminar?.numEmpresas ?? 0"
      :num-ciclos="centroAEliminar?.numCiclos ?? 0"
      @centro-eliminado="onCentroEliminado"
      @cerrar="mostrarEliminarCentro = false"
    />

    <!-- ════════════ MODAL: NUEVO CENTRO EDUCATIVO ════════════ -->
    <CentroEducativoModal
      :visible="mostrarNuevoCentro"
      @centro-creado="onCentroCreado"
      @cerrar="mostrarNuevoCentro = false"
    />

    <!-- ════════════ MODAL: EDITAR CENTRO EDUCATIVO ════════════ -->
    <CentroEducativoModal
      :visible="mostrarEditarCentro"
      :centro="centroAEditar"
      @centro-guardado="onCentroGuardado"
      @cerrar="mostrarEditarCentro = false"
    />

    <!-- ════════════ MODALES: NUEVA / EDITAR EMPRESA ════════════ -->
    <InsertModifyEmpresa
      v-model:mostrarNuevaEmpresa="mostrarNuevaEmpresa"
      v-model:mostrarEditarEmpresa="mostrarEditarEmpresa"
      :familiasProfesionales="familiasProfesionales"
      :empresaAEditar="empresaAEditar"
      @empresa-creada="onEmpresaCreada"
      @empresa-actualizada="onEmpresaActualizada"
    />

    <!-- ════════════════ SNACKBAR ════════════════ -->
    <Transition name="snack">
      <div
        v-if="snackbar.visible"
        class="fixed bottom-6 right-6 z-[60] flex items-center gap-3
               px-5 py-3.5 rounded-2xl shadow-xl text-sm font-bold
               max-w-sm"
        :class="snackbar.tipo === 'ok'
          ? 'bg-[#1F2937] text-white border border-[#333333]'
          : 'bg-red-600 text-white border border-red-500'"
      >
        <svg v-if="snackbar.tipo === 'ok'" class="w-4 h-4 text-[#00A859] shrink-0"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
        </svg>
        <svg v-else class="w-4 h-4 text-white shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ snackbar.mensaje }}
      </div>
    </Transition>

  </div>
</template>

<style scoped>
/* Acordeón expand */
.expand-enter-active,
.expand-leave-active { transition: all 0.25s ease; overflow: hidden; }
.expand-enter-from,
.expand-leave-to    { opacity: 0; transform: translateY(-6px); }

/* Modales */
.modal-fade-enter-active,
.modal-fade-leave-active { transition: all 0.2s ease; }
.modal-fade-enter-from,
.modal-fade-leave-to    { opacity: 0; }
.modal-fade-enter-from > div,
.modal-fade-leave-to > div { transform: scale(0.95) translateY(8px); }

/* Snackbar */
.snack-enter-active,
.snack-leave-active { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.snack-enter-from,
.snack-leave-to     { opacity: 0; transform: translateY(12px) scale(0.95); }
</style>
