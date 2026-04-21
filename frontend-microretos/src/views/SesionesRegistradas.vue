<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import MicroretoModal from '../components/MicroretoModal.vue'

const router = useRouter()

// ─── Datos de sesiones ────────────────────────────────────────────────────────
const STORAGE_KEY = 'dualab_sesiones'
const sesiones    = ref([])

onMounted(() => {
  try {
    sesiones.value = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
  } catch {
    sesiones.value = []
  }
})

function eliminarSesion(id) {
  const lista = sesiones.value.filter(s => s.id !== id)
  localStorage.setItem(STORAGE_KEY, JSON.stringify(lista))
  sesiones.value = lista
  if (sesionAbierta.value?.id === id) sesionAbierta.value = null
}

// ─── Filtros ──────────────────────────────────────────────────────────────────
const filtros = ref({
  titulo:  '',
  desde:   '',
  hasta:   '',
  curso:   '',
  grupo:   '',
  ciclo:   '',
  centro:  '',
})

const sesionesUnicas = computed(() => {
  const centros = [...new Set(sesiones.value.map(s => s.centro_educativo).filter(Boolean))].sort()
  const ciclos  = [...new Set(sesiones.value.map(s => s.ciclo_formativo).filter(Boolean))].sort()
  return { centros, ciclos }
})

const sesionesFiltradas = computed(() => {
  let lista = [...sesiones.value].reverse()

  if (filtros.value.titulo.trim()) {
    const q = filtros.value.titulo.trim().toLowerCase()
    lista = lista.filter(s => (s.microreto_titulo || '').toLowerCase().includes(q))
  }
  if (filtros.value.desde)
    lista = lista.filter(s => s.fecha >= filtros.value.desde)
  if (filtros.value.hasta)
    lista = lista.filter(s => s.fecha <= filtros.value.hasta)
  if (filtros.value.curso)
    lista = lista.filter(s => s.curso === filtros.value.curso)
  if (filtros.value.grupo)
    lista = lista.filter(s => s.grupo === filtros.value.grupo)
  if (filtros.value.ciclo)
    lista = lista.filter(s => (s.ciclo_formativo || '').toLowerCase().includes(filtros.value.ciclo.toLowerCase()))
  if (filtros.value.centro)
    lista = lista.filter(s => s.centro_educativo === filtros.value.centro)

  return lista
})

const hayFiltrosActivos = computed(() =>
  Object.values(filtros.value).some(v => v !== '')
)

function limpiarFiltros() {
  filtros.value = { titulo: '', desde: '', hasta: '', curso: '', grupo: '', ciclo: '', centro: '' }
  pagina.value  = 1
}

// ─── Paginación ───────────────────────────────────────────────────────────────
const pagina           = ref(1)
const POR_PAGINA       = 12

const sesionesVisibles = computed(() => {
  const start = (pagina.value - 1) * POR_PAGINA
  return sesionesFiltradas.value.slice(start, start + POR_PAGINA)
})

const totalPaginas = computed(() =>
  Math.ceil(sesionesFiltradas.value.length / POR_PAGINA)
)

// ─── Modal sesión ─────────────────────────────────────────────────────────────
const sesionAbierta = ref(null)

function verSesion(s)     { sesionAbierta.value = s }
function cerrarSesion()   { sesionAbierta.value = null }

// ─── Modal ficha microreto ────────────────────────────────────────────────────
const microretoModalId = ref(null)

function abrirMicroretoModal(id) { microretoModalId.value = id }
function cerrarMicroretoModal()  { microretoModalId.value = null }

// ─── Stats ────────────────────────────────────────────────────────────────────
const stats = computed(() => {
  const microretos = new Set(sesiones.value.map(s => s.microreto_id).filter(Boolean))
  const centros    = new Set(sesiones.value.map(s => s.centro_educativo).filter(Boolean))
  return {
    total:      sesiones.value.length,
    microretos: microretos.size,
    centros:    centros.size,
  }
})

// ─── Utils ────────────────────────────────────────────────────────────────────
function formatFecha(isoDate) {
  if (!isoDate) return ''
  const d = new Date(isoDate + 'T12:00:00')
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })
}

function irPaginaAnterior() {
  pagina.value--
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function irPaginaSiguiente() {
  pagina.value++
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] font-sans text-[#1F2937]">

    <!-- Fondo decorativo -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[600px] h-[400px]
                bg-[#00A859] opacity-5 blur-[120px] rounded-full pointer-events-none z-0" />

    <div class="relative z-10 max-w-5xl mx-auto px-4 py-8 md:px-8 md:py-12">

      <!-- ─── Cabecera ─────────────────────────────────────────────────────── -->
      <header class="mb-8">
        <!-- Volver -->
        <button @click="router.push('/dashboard')"
                class="inline-flex items-center gap-2 mb-5 text-[10px] font-black uppercase tracking-widest
                       text-gray-400 hover:text-[#00A859] transition-colors group">
          <svg class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform"
               fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
          Volver al dashboard
        </button>

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
          <div>
            <div class="inline-flex items-center gap-2 mb-2 px-3 py-1 rounded-full
                        bg-[#00A859]/10 border border-[#00A859]/20">
              <span class="w-2 h-2 rounded-full bg-[#00A859]" />
              <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">Dashboard docente</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-black tracking-tight text-[#121212]">
              Sesiones <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00A859] to-[#99CC33]">registradas</span>
            </h1>
            <p class="text-gray-500 text-sm mt-1">Consulta y filtra todo el historial de sesiones.</p>
          </div>

          <!-- Stats -->
          <div class="flex flex-wrap gap-3">
            <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-2xl border border-gray-100 shadow-sm">
              <svg class="w-4 h-4 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                         M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
              </svg>
              <span class="font-black text-xl text-[#1F2937]">{{ stats.total }}</span>
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">sesiones</span>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 bg-white rounded-2xl border border-gray-100 shadow-sm">
              <svg class="w-4 h-4 text-[#99CC33]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
              <span class="font-black text-xl text-[#1F2937]">{{ stats.microretos }}</span>
              <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">microretos</span>
            </div>
          </div>
        </div>
      </header>

      <!-- ─── Filtros ──────────────────────────────────────────────────────── -->
      <div class="bg-white rounded-[1.5rem] border border-gray-100 shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
          <p class="text-[10px] font-black uppercase tracking-[0.18em] text-gray-400">Filtrar sesiones</p>
          <button v-if="hayFiltrosActivos"
                  @click="limpiarFiltros"
                  class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-red-400 transition-colors">
            Limpiar filtros
          </button>
        </div>

        <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

          <!-- Búsqueda por título -->
          <div class="lg:col-span-2">
            <label class="field-label">Buscar por título de microreto</label>
            <div class="relative">
              <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300 pointer-events-none"
                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
              </svg>
              <input v-model="filtros.titulo" type="text"
                     placeholder="Buscar por título..."
                     class="field-input pl-10"
                     @input="pagina = 1" />
            </div>
          </div>

          <!-- Centro -->
          <div>
            <label class="field-label">Centro educativo</label>
            <select v-model="filtros.centro" class="field-input" @change="pagina = 1">
              <option value="">Todos los centros</option>
              <option v-for="c in sesionesUnicas.centros" :key="c" :value="c">{{ c }}</option>
            </select>
          </div>

          <!-- Fecha desde -->
          <div>
            <label class="field-label">Desde</label>
            <input v-model="filtros.desde" type="date" class="field-input" @change="pagina = 1" />
          </div>

          <!-- Fecha hasta -->
          <div>
            <label class="field-label">Hasta</label>
            <input v-model="filtros.hasta" type="date" class="field-input" @change="pagina = 1" />
          </div>

          <!-- Ciclo -->
          <div>
            <label class="field-label">Ciclo formativo</label>
            <input v-model="filtros.ciclo" type="text"
                   placeholder="Filtrar por ciclo..."
                   class="field-input"
                   @input="pagina = 1" />
          </div>

          <!-- Curso -->
          <div>
            <label class="field-label">Curso</label>
            <div class="flex gap-2 mt-1">
              <button v-for="c in ['', '1º', '2º']" :key="c"
                      @click="filtros.curso = c; pagina = 1"
                      class="flex-1 py-2 rounded-xl text-xs font-black uppercase tracking-widest border transition-all"
                      :class="filtros.curso === c
                        ? 'bg-[#00A859] border-[#00A859] text-white'
                        : 'bg-gray-50 border-gray-200 text-gray-500 hover:border-[#00A859]/40 hover:text-[#00A859]'">
                {{ c === '' ? 'Todos' : c }}
              </button>
            </div>
          </div>

          <!-- Grupo -->
          <div>
            <label class="field-label">Grupo</label>
            <div class="flex gap-1.5 mt-1">
              <button v-for="g in ['', 'A', 'B', 'C', 'D']" :key="g"
                      @click="filtros.grupo = g; pagina = 1"
                      class="flex-1 py-2 rounded-xl text-xs font-black uppercase border transition-all"
                      :class="filtros.grupo === g
                        ? 'bg-[#99CC33] border-[#99CC33] text-white'
                        : 'bg-gray-50 border-gray-200 text-gray-500 hover:border-[#99CC33]/40 hover:text-[#99CC33]'">
                {{ g === '' ? 'Todos' : g }}
              </button>
            </div>
          </div>

        </div>

        <!-- Contador de resultados -->
        <div class="px-6 py-3 bg-gray-50 border-t border-gray-100">
          <p class="text-[10px] font-bold text-gray-400">
            <span v-if="hayFiltrosActivos">
              {{ sesionesFiltradas.length }} {{ sesionesFiltradas.length === 1 ? 'sesión encontrada' : 'sesiones encontradas' }}
              <span class="text-gray-300">de {{ stats.total }} totales</span>
            </span>
            <span v-else>{{ stats.total }} {{ stats.total === 1 ? 'sesión' : 'sesiones' }} en total</span>
          </p>
        </div>
      </div>

      <!-- ─── Grid de sesiones ──────────────────────────────────────────────── -->

      <!-- Estado vacío global -->
      <div v-if="sesiones.length === 0"
           class="bg-white rounded-[1.5rem] border border-gray-100 shadow-sm px-8 py-16 text-center">
        <div class="w-16 h-16 rounded-full bg-gray-50 border border-gray-100
                    flex items-center justify-center mx-auto mb-4">
          <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
          </svg>
        </div>
        <p class="text-sm text-gray-400 font-medium mb-4">Aún no hay sesiones registradas.</p>
        <button @click="router.push('/dashboard')"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#00A859] text-white
                       text-xs font-black uppercase tracking-widest hover:bg-[#00A859]/90 transition-all">
          Registrar primera sesión
        </button>
      </div>

      <!-- Sin resultados con filtros -->
      <div v-else-if="sesionesFiltradas.length === 0"
           class="bg-white rounded-[1.5rem] border border-gray-100 shadow-sm px-8 py-12 text-center">
        <svg class="w-8 h-8 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
        </svg>
        <p class="text-sm text-gray-400 font-medium mb-3">Sin sesiones para esos filtros.</p>
        <button @click="limpiarFiltros"
                class="text-xs font-black uppercase tracking-widest text-[#00A859] hover:underline">
          Limpiar filtros
        </button>
      </div>

      <!-- Grid de cards -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="s in sesionesVisibles" :key="s.id"
             class="bg-white rounded-[1.25rem] border border-gray-100 shadow-sm overflow-hidden
                    hover:border-[#00A859]/30 hover:shadow-md transition-all group cursor-pointer"
             @click="verSesion(s)">

          <!-- Card header -->
          <div class="px-5 pt-5 pb-4 border-b border-gray-50">
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-black text-[#1F2937] leading-snug line-clamp-2
                          group-hover:text-[#00A859] transition-colors">
                  {{ s.microreto_titulo || '(sin título)' }}
                </p>
                <p class="text-[10px] font-bold text-[#00A859] mt-1">
                  {{ formatFecha(s.fecha) }}
                </p>
              </div>
              <!-- Acciones en hover -->
              <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-all flex-shrink-0">
                <button v-if="s.microreto_id"
                        @click.stop="abrirMicroretoModal(s.microreto_id)"
                        class="p-1.5 rounded-lg bg-[#00A859]/10 text-[#00A859] hover:bg-[#00A859]/20 transition-all"
                        title="Ver ficha del microreto">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                             a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                </button>
                <button @click.stop="eliminarSesion(s.id)"
                        class="p-1.5 rounded-lg hover:bg-red-50 text-gray-300 hover:text-red-400 transition-all"
                        title="Eliminar sesión">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                          d="M6 18L18 6M6 6l12 12"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Card body -->
          <div class="px-5 py-4 space-y-2">
            <!-- Tags -->
            <div class="flex flex-wrap gap-1.5">
              <span v-if="s.curso"       class="tag tag-green">{{ s.curso }}</span>
              <span v-if="s.grupo"       class="tag tag-lime">Grupo {{ s.grupo }}</span>
              <span v-if="s.num_alumnos" class="tag tag-gray">{{ s.num_alumnos }} alumnos</span>
            </div>
            <!-- Info secundaria -->
            <div class="space-y-1">
              <p v-if="s.centro_educativo" class="text-[10px] text-gray-500 flex items-center gap-1.5">
                <svg class="w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                {{ s.centro_educativo }}
              </p>
              <p v-if="s.ciclo_formativo" class="text-[10px] text-gray-500 flex items-center gap-1.5">
                <svg class="w-3 h-3 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <span class="truncate">{{ s.ciclo_formativo }}</span>
              </p>
              <p v-if="s.notas" class="text-[10px] text-gray-400 leading-relaxed line-clamp-2 italic">
                "{{ s.notas }}"
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── Paginación ────────────────────────────────────────────────────── -->
      <div v-if="totalPaginas > 1"
           class="mt-8 flex items-center justify-center gap-3">
        <button @click="irPaginaAnterior"
                :disabled="pagina === 1"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200
                       text-xs font-black uppercase tracking-widest text-gray-500
                       hover:border-[#00A859] hover:text-[#00A859]
                       disabled:opacity-30 disabled:cursor-not-allowed transition-all">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
          Anterior
        </button>

        <span class="px-4 py-2 bg-white border border-gray-200 rounded-xl
                     text-xs font-black text-gray-500 uppercase tracking-widest">
          {{ pagina }} / {{ totalPaginas }}
        </span>

        <button @click="irPaginaSiguiente"
                :disabled="pagina === totalPaginas"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200
                       text-xs font-black uppercase tracking-widest text-gray-500
                       hover:border-[#00A859] hover:text-[#00A859]
                       disabled:opacity-30 disabled:cursor-not-allowed transition-all">
          Siguiente
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
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
        <div @click="cerrarSesion"
             class="absolute inset-0 bg-black/40 backdrop-blur-sm" />

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
              <button @click="cerrarSesion"
                      class="flex-shrink-0 p-2 rounded-xl hover:bg-gray-100 text-gray-400
                             hover:text-gray-600 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
            <div class="flex flex-wrap gap-1.5 mt-3">
              <span v-if="sesionAbierta.curso"       class="tag tag-green">{{ sesionAbierta.curso }}</span>
              <span v-if="sesionAbierta.grupo"       class="tag tag-lime">Grupo {{ sesionAbierta.grupo }}</span>
              <span v-if="sesionAbierta.num_alumnos" class="tag tag-gray">{{ sesionAbierta.num_alumnos }} alumnos</span>
            </div>
          </div>

          <!-- Cuerpo -->
          <div class="px-7 py-5 space-y-4">
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
            <div v-if="sesionAbierta.notas"
                 class="p-4 rounded-xl bg-[#F8FAFC] border border-gray-100">
              <p class="text-[9px] font-black uppercase tracking-wider text-gray-400 mb-1.5">Notas adicionales</p>
              <p class="text-sm text-[#1F2937] leading-relaxed">{{ sesionAbierta.notas }}</p>
            </div>
          </div>

          <!-- Pie -->
          <div class="px-7 py-4 bg-[#F8FAFC] border-t border-gray-100 flex items-center justify-between gap-4">
            <button @click="eliminarSesion(sesionAbierta.id)"
                    class="text-xs text-gray-400 hover:text-red-400 font-black uppercase
                           tracking-widest transition-colors">
              Eliminar sesión
            </button>
            <button v-if="sesionAbierta.microreto_id"
                    @click="cerrarSesion(); abrirMicroretoModal(sesionAbierta.microreto_id)"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#00A859] text-white
                           text-xs font-black uppercase tracking-widest hover:bg-[#00A859]/90
                           transition-all shadow-sm">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
              </svg>
              Ver ficha del microreto
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>

  <!-- Modal ficha de microreto -->
  <MicroretoModal :microreto-id="microretoModalId" @close="cerrarMicroretoModal" />

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

.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 200ms ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-fade-enter-from .relative { transform: scale(0.95) translateY(8px); opacity: 0; }
</style>
