<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'
import api from '../api.js'

const router    = useRouter()
const authStore = useAuthStore()
const isLoaded  = ref(false)

// ── Datos ─────────────────────────────────────────────────────────────────────
const sesiones          = ref([])
const proyectos         = ref([])
const cargandoSesiones  = ref(true)
const cargandoProyectos = ref(true)

// ── Listas derivadas ──────────────────────────────────────────────────────────
const ultimasSesiones = computed(() => sesiones.value.slice(0, 3))

const proyectosValidados = computed(() =>
  proyectos.value.filter(p => p.estado === 'publicado' && p.empresa_validado === true)
)

const proyectosPendientes = computed(() =>
  proyectos.value.filter(p => p.estado === 'publicado' && !p.empresa_validado)
)

const proyectosEnEdicion = computed(() =>
  proyectos.value.filter(p => p.estado === 'borrador')
)

const hayProyectos = computed(() =>
  proyectosValidados.value.length > 0 ||
  proyectosPendientes.value.length > 0 ||
  proyectosEnEdicion.value.length > 0
)

function formatFecha(isoDate) {
  if (!isoDate) return ''
  const d = new Date(isoDate + 'T12:00:00')
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' })
}

const primerNombre = computed(() => {
  const n = authStore.userName || ''
  return n.split(' ')[0] || n
})

const userCentroNombre = computed(() => authStore.userCentroNombre || '')
const userCentroImg    = computed(() => authStore.userCentroImg || '')

// ── Carga ──────────────────────────────────────────────────────────────────────
onMounted(() => {
  setTimeout(() => { isLoaded.value = true }, 100)
  cargarSesiones()
  cargarProyectos()
})

async function cargarSesiones() {
  try {
    const { data } = await api.get('/sesiones')
    sesiones.value = data
  } catch { /* silencioso */ } finally {
    cargandoSesiones.value = false
  }
}

async function cargarProyectos() {
  try {
    const { data } = await api.get('/startup/proyectos')
    proyectos.value = data
  } catch { /* silencioso */ } finally {
    cargandoProyectos.value = false
  }
}

// ── Navegación ─────────────────────────────────────────────────────────────────
const irA = (path) => router.push(path)
const irAStartupFiltrado = (filtro) => router.push({ path: '/startup-day', query: { filtro } })

// ── Calendario ─────────────────────────────────────────────────────────────────
const calendarDate = ref(new Date())

const calendarMonthLabel = computed(() =>
  calendarDate.value.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' })
)

const calendarDays = computed(() => {
  const year  = calendarDate.value.getFullYear()
  const month = calendarDate.value.getMonth()
  const firstWeekday = new Date(year, month, 1).getDay()
  const daysInMonth  = new Date(year, month + 1, 0).getDate()
  const offset = (firstWeekday + 6) % 7 // semana empieza en lunes
  const days = Array(offset).fill(null)
  for (let d = 1; d <= daysInMonth; d++) days.push(d)
  return days
})

const _today = new Date()
const isToday = (day) =>
  day === _today.getDate() &&
  calendarDate.value.getMonth() === _today.getMonth() &&
  calendarDate.value.getFullYear() === _today.getFullYear()

function prevMonth() {
  const d = new Date(calendarDate.value); d.setDate(1); d.setMonth(d.getMonth() - 1)
  calendarDate.value = d
}
function nextMonth() {
  const d = new Date(calendarDate.value); d.setDate(1); d.setMonth(d.getMonth() + 1)
  calendarDate.value = d
}

// ── Eventos del calendario ─────────────────────────────────────────────────────
const eventos       = ref([])
const selectedDate  = ref(null)
const newEventText  = ref('')
const newEventColor = ref('#00A859')
const eventColors   = ['#00A859', '#99CC33', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6']

function selectCalDay(day) {
  if (!day) return
  const m = calendarDate.value.getMonth()
  const y = calendarDate.value.getFullYear()
  if (selectedDate.value?.day === day && selectedDate.value?.month === m && selectedDate.value?.year === y) {
    selectedDate.value = null
  } else {
    selectedDate.value = { day, month: m, year: y }
    newEventText.value = ''
  }
}

function addEvento() {
  if (!newEventText.value.trim() || !selectedDate.value) return
  eventos.value.push({ ...selectedDate.value, text: newEventText.value.trim(), color: newEventColor.value })
  newEventText.value = ''
  selectedDate.value = null
}

function removeEvento(i) { eventos.value.splice(i, 1) }

const eventosDelMes = computed(() =>
  eventos.value.filter(e =>
    e.month === calendarDate.value.getMonth() && e.year === calendarDate.value.getFullYear()
  )
)
const dayEvents = (day) =>
  eventos.value.filter(e =>
    e.day === day && e.month === calendarDate.value.getMonth() && e.year === calendarDate.value.getFullYear()
  )
const isDaySelected = (day) =>
  selectedDate.value?.day === day &&
  selectedDate.value?.month === calendarDate.value.getMonth() &&
  selectedDate.value?.year  === calendarDate.value.getFullYear()
</script>

<template>
  <div class="min-h-screen font-sans text-[#1F2937] pt-12">
    <div class="max-w-6xl mx-auto px-6 sm:px-12 lg:px-16 py-10">

      <!-- ══ Cabecera bienvenida ══════════════════════════════════════════════════ -->
      <div class="relative overflow-hidden bg-[#1F2937] rounded-2xl p-4 sm:p-5 mb-4
                  transition-all duration-700"
           :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">

        <div class="absolute top-0 right-0 w-48 h-48 bg-[#00A859]/10 rounded-full
                    translate-x-1/3 -translate-y-1/3 blur-[60px] pointer-events-none"></div>

        <div class="relative z-10 flex items-center justify-between gap-3">
          <div>
            <div class="inline-flex items-center gap-1.5 bg-[#00A859]/15 border border-[#00A859]/25
                        rounded-full px-2.5 py-0.5 mb-2">
              <span class="w-1.5 h-1.5 rounded-full bg-[#99CC33] animate-pulse shrink-0"></span>
              <span class="text-[#99CC33] text-[9px] font-black uppercase tracking-widest">Perfil docente</span>
            </div>
            <h1 class="text-lg sm:text-xl font-black text-white tracking-tight">
              Bienvenido/a, <span class="text-[#99CC33]">{{ primerNombre }}</span>
            </h1>
            <div v-if="userCentroNombre" class="flex items-center gap-1.5 mt-1">
              <svg class="w-3.5 h-3.5 text-white/40 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
              <span class="text-white/55 text-xs font-semibold">{{ userCentroNombre }}</span>
            </div>
            <p class="text-white/35 text-xs mt-0.5 font-medium">Panel de control · DuaLab</p>
          </div>

          <div class="hidden sm:flex w-11 h-11 rounded-xl bg-[#00A859]/15 border border-[#00A859]/25
                      items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 14l9-5-9-5-9 5 9 5z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- ══ Contadores de proyectos ════════════════════════════════════════════════ -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6 transition-all duration-700 delay-75"
           :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">

        <!-- Proyectos validados -->
        <button @click="irAStartupFiltrado('proyecto')"
                class="group bg-white border border-gray-100 rounded-2xl px-4 py-3 text-left
                       hover:border-[#00A859]/30 hover:shadow-sm transition-all duration-200">
          <div v-if="!cargandoProyectos" class="text-2xl font-black text-[#00A859] tabular-nums leading-none mb-1">
            {{ proyectosValidados.length }}
          </div>
          <div v-else class="h-7 w-8 bg-gray-100 rounded-lg animate-pulse mb-1"></div>
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-tight">Validados</p>
          <p class="text-[9px] font-black text-[#00A859]/60 uppercase tracking-widest mt-1
                    group-hover:text-[#00A859] transition-colors">Ver todos →</p>
        </button>

        <!-- Pendientes de validar -->
        <button @click="irAStartupFiltrado('propuesta')"
                class="group bg-white border border-gray-100 rounded-2xl px-4 py-3 text-left
                       hover:border-amber-300/50 hover:shadow-sm transition-all duration-200">
          <div v-if="!cargandoProyectos" class="text-2xl font-black text-amber-500 tabular-nums leading-none mb-1">
            {{ proyectosPendientes.length }}
          </div>
          <div v-else class="h-7 w-8 bg-gray-100 rounded-lg animate-pulse mb-1"></div>
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-tight">Pendientes</p>
          <p class="text-[9px] font-black text-amber-400/60 uppercase tracking-widest mt-1
                    group-hover:text-amber-500 transition-colors">Ver todos →</p>
        </button>

        <!-- Total proyectos -->
        <button @click="irAStartupFiltrado('todos')"
                class="group bg-white border border-gray-100 rounded-2xl px-4 py-3 text-left
                       hover:border-blue-300/50 hover:shadow-sm transition-all duration-200">
          <div v-if="!cargandoProyectos" class="text-2xl font-black text-blue-500 tabular-nums leading-none mb-1">
            {{ proyectos.length }}
          </div>
          <div v-else class="h-7 w-8 bg-gray-100 rounded-lg animate-pulse mb-1"></div>
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest leading-tight">Total proyectos</p>
          <p class="text-[9px] font-black text-blue-400/60 uppercase tracking-widest mt-1
                    group-hover:text-blue-500 transition-colors">Ver todos →</p>
        </button>

        <!-- Alumnado asignado (futuro) -->
        <div class="bg-white border border-dashed border-gray-200 rounded-2xl px-4 py-3">
          <div class="text-2xl font-black text-gray-300 tabular-nums leading-none mb-1">—</div>
          <p class="text-[10px] font-bold text-gray-300 uppercase tracking-widest leading-tight">Alumnado</p>
          <p class="text-[9px] font-black text-gray-300 uppercase tracking-widest mt-1">Próximamente</p>
        </div>

      </div>

      <!-- ── Fila superior: Mis sesiones | Acciones rápidas | Mi cuenta ─────────── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-start
                  transition-all duration-700 delay-150"
           :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">

        <!-- Mis sesiones -->
        <section class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
          <div class="bg-[#374151] px-4 py-3 flex items-center gap-3">
            <div class="w-7 h-7 rounded-lg bg-blue-400/20 border border-blue-400/25
                        flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                     M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
            </div>
            <h3 class="text-white font-black text-sm">Mis sesiones</h3>
          </div>

          <div v-if="cargandoSesiones" class="p-6 flex justify-center">
            <svg class="animate-spin w-5 h-5 text-[#00A859]" viewBox="0 0 24 24">
              <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
            </svg>
          </div>

          <div v-else-if="sesiones.length === 0" class="px-5 py-8 text-center">
            <div class="w-10 h-10 rounded-full bg-gray-50 border border-gray-100
                        flex items-center justify-center mx-auto mb-3">
              <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                     M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
            </div>
            <p class="text-xs text-gray-400 font-medium mb-3">Aún no hay sesiones registradas.</p>
            <button @click="irA('/dashboard')"
                    class="text-[10px] font-black uppercase tracking-widest text-[#00A859]
                           hover:text-[#00A859]/70 transition-colors">
              Registrar primera sesión →
            </button>
          </div>

          <template v-else>
            <ul class="divide-y divide-gray-50">
              <li v-for="s in ultimasSesiones" :key="s.id"
                  class="px-4 py-3 hover:bg-gray-50/60 transition-colors group cursor-pointer"
                  @click="irA('/dashboard/sesiones')">
                <div class="flex items-start gap-2">
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-black text-[#1F2937] leading-snug truncate
                              group-hover:text-[#00A859] transition-colors">
                      {{ s.microreto_titulo || '(sin título)' }}
                    </p>
                    <p class="text-[10px] text-[#00A859] font-bold mt-0.5">
                      {{ formatFecha(s.fecha) }}
                    </p>
                    <div class="flex flex-wrap gap-1 mt-1">
                      <span v-if="s.curso"
                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wide
                                   bg-[#00A859]/10 text-[#00A859]">
                        {{ s.curso }}
                      </span>
                      <span v-if="s.grupo"
                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wide
                                   bg-[#99CC33]/12 text-[#5a7a00]">
                        Gr. {{ s.grupo }}
                      </span>
                      <span v-if="s.num_alumnos"
                            class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wide
                                   bg-gray-100 text-gray-500">
                        {{ s.num_alumnos }} al.
                      </span>
                    </div>
                  </div>
                  <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-[#00A859] shrink-0 mt-0.5 transition-colors"
                       fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </div>
              </li>
            </ul>
            <div class="px-4 py-3 border-t border-gray-50 bg-blue-50/50">
              <button @click="irA('/dashboard/sesiones')"
                      class="w-full flex items-center justify-center gap-1.5 text-[10px] font-black
                             uppercase tracking-widest text-blue-500 hover:text-blue-600
                             transition-colors py-0.5">
                Ver todas las sesiones
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
          </template>
        </section>

        <!-- Acciones rápidas -->
        <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
          <div class="bg-[#374151] px-4 py-3 flex items-center gap-3">
            <div class="w-7 h-7 rounded-lg bg-[#00A859]/20 border border-[#00A859]/25
                        flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 10V3L4 14h7v7l9-11h-7z"/>
              </svg>
            </div>
            <h3 class="text-white font-black text-sm">Acciones rápidas</h3>
          </div>
          <div class="p-3 space-y-2">

            <button @click="irA('/microretos')"
              class="group flex items-center gap-2.5 p-3 rounded-xl text-left w-full
                     bg-[#00A859]/8 border border-[#00A859]/20
                     hover:bg-[#00A859]/15 hover:border-[#00A859]/40 hover:shadow-sm
                     transition-all duration-200">
              <div class="w-7 h-7 rounded-lg bg-[#00A859]/15 border border-[#00A859]/20
                          flex items-center justify-center shrink-0
                          group-hover:bg-[#00A859]/25 transition-colors">
                <svg class="w-3.5 h-3.5 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
              </div>
              <span class="flex-1 min-w-0 text-xs font-black text-[#1F2937] truncate
                           group-hover:text-[#00A859] transition-colors">Nuevo reto</span>
              <svg class="w-3 h-3 text-[#00A859]/40 shrink-0 group-hover:text-[#00A859]
                          group-hover:translate-x-0.5 transition-all"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
              </svg>
            </button>

            <button @click="irA('/biblioteca')"
              class="group flex items-center gap-2.5 p-3 rounded-xl text-left w-full
                     bg-[#99CC33]/8 border border-[#99CC33]/25
                     hover:bg-[#99CC33]/15 hover:border-[#99CC33]/45 hover:shadow-sm
                     transition-all duration-200">
              <div class="w-7 h-7 rounded-lg bg-[#99CC33]/15 border border-[#99CC33]/25
                          flex items-center justify-center shrink-0
                          group-hover:bg-[#99CC33]/25 transition-colors">
                <svg class="w-3.5 h-3.5 text-[#6EA820]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 19.5A2.5 2.5 0 016.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 014 22v-15A2.5 2.5 0 016.5 2z"/>
                </svg>
              </div>
              <span class="flex-1 min-w-0 text-xs font-black text-[#1F2937] truncate
                           group-hover:text-[#6EA820] transition-colors">Biblioteca de retos</span>
              <svg class="w-3 h-3 text-[#99CC33]/50 shrink-0 group-hover:text-[#6EA820]
                          group-hover:translate-x-0.5 transition-all"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
              </svg>
            </button>

            <button @click="irA('/startup-day/crear')"
              class="group flex items-center gap-2.5 p-3 rounded-xl text-left w-full
                     bg-orange-50/60 border border-orange-200/60
                     hover:bg-orange-50 hover:border-orange-300/60 hover:shadow-sm
                     transition-all duration-200">
              <div class="w-7 h-7 rounded-lg bg-orange-100/70 border border-orange-200/60
                          flex items-center justify-center shrink-0
                          group-hover:bg-orange-100 transition-colors">
                <svg class="w-3.5 h-3.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
              </div>
              <span class="flex-1 min-w-0 text-xs font-black text-[#1F2937] truncate
                           group-hover:text-orange-600 transition-colors">Nuevo proyecto</span>
              <svg class="w-3 h-3 text-orange-400/50 shrink-0 group-hover:text-orange-500
                          group-hover:translate-x-0.5 transition-all"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
              </svg>
            </button>

          </div>
        </div>

      </div><!-- /fila superior -->

      <!-- ── Mis proyectos — ancho completo con grupos en horizontal ──────────── -->
      <section class="mt-4 transition-all duration-700 delay-[200ms]"
               :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">

        <div class="flex items-center gap-3 mb-3">
          <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 shrink-0">
            Mis proyectos
          </span>
          <div class="flex-1 h-px bg-gray-200"></div>
        </div>

        <!-- Cargando -->
        <div v-if="cargandoProyectos"
             class="bg-white border border-gray-100 rounded-2xl p-6 flex justify-center">
          <svg class="animate-spin w-5 h-5 text-[#00A859]" viewBox="0 0 24 24">
            <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
          </svg>
        </div>

        <!-- Sin proyectos -->
        <div v-else-if="!hayProyectos"
             class="bg-white border border-gray-100 rounded-2xl px-5 py-8 text-center">
          <div class="w-10 h-10 rounded-full bg-gray-50 border border-gray-100
                      flex items-center justify-center mx-auto mb-3">
            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2
                   m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
          </div>
          <p class="text-xs text-gray-400 font-medium mb-3">Aún no hay proyectos creados.</p>
          <button @click="irA('/startup-day')"
                  class="text-[10px] font-black uppercase tracking-widest text-orange-500
                         hover:text-orange-400 transition-colors">
            Ir a Startup Day →
          </button>
        </div>

        <!-- Grupos en horizontal -->
        <div v-else class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
          <div class="flex flex-col sm:flex-row divide-y sm:divide-y-0 sm:divide-x divide-gray-100">

            <!-- Validados -->
            <div v-if="proyectosValidados.length > 0" class="flex-1 min-w-0">
              <div class="px-4 py-2 bg-[#374151] flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-[#00A859] shrink-0"></span>
                <span class="text-[9px] font-black uppercase tracking-widest text-white/70">Validados</span>
              </div>
              <ul class="space-y-2 px-4 py-3">
                <li v-for="p in proyectosValidados.slice(0, 4)" :key="p.id"
                    class="flex items-center gap-2.5 cursor-pointer group"
                    @click="irA('/startup-day/' + p.uuid)">
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-[#1F2937] truncate group-hover:text-[#00A859] transition-colors">
                      {{ p.titulo || 'Sin título' }}
                    </p>
                    <p v-if="p.empresa_nombre" class="text-[10px] text-gray-400 font-medium truncate">
                      {{ p.empresa_nombre }}
                    </p>
                  </div>
                  <span class="text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full
                               bg-[#00A859]/10 text-[#00A859] border border-[#00A859]/15 shrink-0">
                    ✓
                  </span>
                </li>
              </ul>
            </div>

            <!-- Pendientes de validar -->
            <div v-if="proyectosPendientes.length > 0" class="flex-1 min-w-0">
              <div class="px-4 py-2 bg-[#374151] flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-orange-400 shrink-0"></span>
                <span class="text-[9px] font-black uppercase tracking-widest text-white/70">Pendientes</span>
              </div>
              <ul class="space-y-2 px-4 py-3">
                <li v-for="p in proyectosPendientes.slice(0, 4)" :key="p.id"
                    class="flex items-center gap-2.5 cursor-pointer group"
                    @click="irA('/startup-day/' + p.uuid)">
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-[#1F2937] truncate group-hover:text-orange-600 transition-colors">
                      {{ p.titulo || 'Sin título' }}
                    </p>
                    <p v-if="p.empresa_nombre" class="text-[10px] text-gray-400 font-medium truncate">
                      {{ p.empresa_nombre }}
                    </p>
                  </div>
                  <span class="text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full
                               bg-orange-50 text-orange-600 border border-orange-200 shrink-0">
                    ···
                  </span>
                </li>
              </ul>
            </div>

            <!-- En edición -->
            <div v-if="proyectosEnEdicion.length > 0" class="flex-1 min-w-0">
              <div class="px-4 py-2 bg-[#374151] flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 shrink-0"></span>
                <span class="text-[9px] font-black uppercase tracking-widest text-white/70">En edición</span>
              </div>
              <ul class="space-y-2 px-4 py-3">
                <li v-for="p in proyectosEnEdicion.slice(0, 4)" :key="p.id"
                    class="flex items-center gap-2.5 cursor-pointer group"
                    @click="irA('/startup-day/' + p.uuid)">
                  <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-[#1F2937] truncate group-hover:text-gray-600 transition-colors">
                      {{ p.titulo || 'Sin título' }}
                    </p>
                    <p v-if="p.empresa_nombre" class="text-[10px] text-gray-400 font-medium truncate">
                      {{ p.empresa_nombre }}
                    </p>
                  </div>
                  <span class="text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full
                               bg-gray-100 text-gray-500 border border-gray-200 shrink-0">
                    ✎
                  </span>
                </li>
              </ul>
            </div>

          </div>

          <!-- Ver todos -->
          <div class="border-t border-gray-100 px-4 py-3
                      bg-gradient-to-r from-[#00A859]/8 via-[#00A859]/4 to-transparent">
            <button @click="irAStartupFiltrado('todos')"
                    class="w-full flex items-center justify-center gap-1.5 text-[10px] font-black
                           uppercase tracking-widest text-[#00A859] hover:text-[#007a40]
                           transition-colors py-0.5">
              Ver todos los proyectos
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </button>
          </div>
        </div>

      </section>

      <!-- ── Herramientas + Mi cuenta ────────────────────────────────────────── -->
      <section class="mt-4 transition-all duration-700 delay-[300ms]"
               :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">

        <div class="flex items-center gap-3 mb-3">
          <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 shrink-0">
            Herramientas
          </span>
          <div class="flex-1 h-px bg-gray-200"></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

          <!-- Retos -->
          <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
            <div class="bg-[#374151] px-5 py-4 flex items-center gap-3">
              <div class="w-8 h-8 rounded-xl bg-[#00A859]/20 border border-[#00A859]/25
                          flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
              </div>
              <h3 class="text-white font-black text-sm">Retos</h3>
            </div>
            <div class="p-3 space-y-2">
              <button @click="irA('/microretos')"
                class="group w-full flex items-center gap-3 p-3.5 rounded-xl text-left
                       bg-[#00A859]/5 border border-[#00A859]/15
                       hover:bg-[#00A859]/12 hover:border-[#00A859]/35 hover:shadow-sm
                       transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-[#00A859]/15 border border-[#00A859]/20
                            flex items-center justify-center shrink-0
                            group-hover:bg-[#00A859]/25 transition-colors duration-200">
                  <svg class="w-5 h-5 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 10V3L4 14h7v7l9-11h-7z"/>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-[#1F2937] font-black text-sm leading-tight">Generador de retos</p>
                  <p class="text-gray-400 text-xs mt-0.5 font-medium">Crea retos con IA para tu alumnado</p>
                </div>
                <svg class="w-4 h-4 text-[#00A859]/30 shrink-0 group-hover:text-[#00A859]/70
                            group-hover:translate-x-0.5 transition-all duration-200"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
              </button>

              <button @click="irA('/biblioteca')"
                class="group w-full flex items-center gap-3 p-3.5 rounded-xl text-left
                       bg-[#99CC33]/5 border border-[#99CC33]/15
                       hover:bg-[#99CC33]/12 hover:border-[#99CC33]/35 hover:shadow-sm
                       transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-[#99CC33]/15 border border-[#99CC33]/20
                            flex items-center justify-center shrink-0
                            group-hover:bg-[#99CC33]/25 transition-colors duration-200">
                  <svg class="w-5 h-5 text-[#6EA820]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 19.5A2.5 2.5 0 016.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 014 22v-15A2.5 2.5 0 016.5 2z"/>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-[#1F2937] font-black text-sm leading-tight">Biblioteca de retos</p>
                  <p class="text-gray-400 text-xs mt-0.5 font-medium">Consulta y comparte retos con QR</p>
                </div>
                <svg class="w-4 h-4 text-[#99CC33]/40 shrink-0 group-hover:text-[#6EA820]/70
                            group-hover:translate-x-0.5 transition-all duration-200"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Taller de Ideas -->
          <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
            <div class="bg-[#374151] px-5 py-4 flex items-center gap-3">
              <div class="w-8 h-8 rounded-xl bg-amber-400/20 border border-amber-400/25
                          flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
              </div>
              <h3 class="text-white font-black text-sm">Taller de Ideas</h3>
            </div>
            <div class="p-3 space-y-2">
              <button @click="irA('/dashboard')"
                class="group w-full flex items-center gap-3 p-3.5 rounded-xl text-left
                       bg-amber-400/5 border border-amber-400/15
                       hover:bg-amber-400/10 hover:border-amber-400/35 hover:shadow-sm
                       transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-amber-400/15 border border-amber-400/20
                            flex items-center justify-center shrink-0
                            group-hover:bg-amber-400/25 transition-colors duration-200">
                  <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                         M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-[#1F2937] font-black text-sm leading-tight">Consultar / Crear sesiones</p>
                  <p class="text-gray-400 text-xs mt-0.5 font-medium">Registra sesiones de trabajo con retos</p>
                </div>
                <svg class="w-4 h-4 text-amber-400/30 shrink-0 group-hover:text-amber-500/70
                            group-hover:translate-x-0.5 transition-all duration-200"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
              </button>

              <button @click="irA('/startup-day')"
                class="group w-full flex items-center gap-3 p-3.5 rounded-xl text-left
                       bg-orange-400/5 border border-orange-400/15
                       hover:bg-orange-400/10 hover:border-orange-400/35 hover:shadow-sm
                       transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-orange-400/15 border border-orange-400/20
                            flex items-center justify-center shrink-0
                            group-hover:bg-orange-400/25 transition-colors duration-200">
                  <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2
                         m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-[#1F2937] font-black text-sm leading-tight">Ver proyectos</p>
                  <p class="text-gray-400 text-xs mt-0.5 font-medium">Gestiona los proyectos del Taller de Ideas</p>
                </div>
                <svg class="w-4 h-4 text-orange-400/30 shrink-0 group-hover:text-orange-500/70
                            group-hover:translate-x-0.5 transition-all duration-200"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Mi cuenta -->
          <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
            <div class="bg-[#374151] px-5 py-4 flex items-center gap-3">
              <div class="w-8 h-8 rounded-xl bg-gray-400/20 border border-gray-400/25
                          flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <circle cx="12" cy="8" r="4" stroke-width="2"/>
                  <path stroke-width="2" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                </svg>
              </div>
              <h3 class="text-white font-black text-sm">Mi cuenta</h3>
            </div>
            <div class="p-3 space-y-2">

              <button @click="irA('/mi-usuario')"
                class="group w-full flex items-center gap-3 p-3.5 rounded-xl text-left
                       bg-gray-50 border border-gray-100
                       hover:border-gray-300/60 hover:shadow-sm transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-gray-100 border border-gray-200
                            flex items-center justify-center shrink-0
                            group-hover:bg-gray-200 transition-colors duration-200">
                  <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="4" stroke-width="2"/>
                    <path stroke-width="2" d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-[#1F2937] font-black text-sm leading-tight">Mi usuario</p>
                  <p class="text-gray-400 text-xs mt-0.5 font-medium">Edita tu perfil</p>
                </div>
                <svg class="w-4 h-4 text-gray-300 shrink-0 group-hover:text-gray-500
                            group-hover:translate-x-0.5 transition-all duration-200"
                  fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
              </button>

              <button v-if="authStore.isAdmin" @click="irA('/admin/usuarios')"
                class="group w-full flex items-center gap-3 p-3.5 rounded-xl text-left
                       bg-purple-50/50 border border-purple-100
                       hover:border-purple-300/60 hover:shadow-sm transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-200
                            flex items-center justify-center shrink-0
                            group-hover:bg-purple-100 transition-colors duration-200">
                  <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857
                         M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857
                         m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-[#1F2937] font-black text-sm leading-tight">Gestión de usuarios</p>
                  <p class="text-gray-400 text-xs mt-0.5 font-medium">Centro educativo</p>
                </div>
                <span class="text-[9px] font-black uppercase tracking-wider text-purple-600
                             bg-purple-50 border border-purple-200 px-2 py-0.5 rounded-full shrink-0">
                  Admin
                </span>
              </button>

            </div>
          </div>

        </div>
      </section>

      <!-- ── Noticias + Eventos ────────────────────────────────────────────────── -->
      <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4
                  transition-all duration-700 delay-[400ms]"
           :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">

        <!-- Últimas noticias — Pinterest -->
        <div>
          <div class="flex items-center gap-3 mb-3">
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 shrink-0">
              Últimas noticias
            </span>
            <div class="flex-1 h-px bg-gray-200"></div>
          </div>
          <div class="columns-2 gap-3 space-y-3">

            <div class="break-inside-avoid rounded-2xl overflow-hidden shadow-sm border border-white/50 cursor-default">
              <div class="h-40 bg-gradient-to-br from-[#00A859] via-[#00A859]/80 to-[#99CC33]/60
                          flex flex-col justify-end p-3">
                <span class="text-[8px] font-black uppercase tracking-widest text-white/70 mb-1">
                  Convocatoria
                </span>
                <p class="text-white font-black text-xs leading-snug">
                  Startup Day 2025-2026 · Inscripciones abiertas
                </p>
              </div>
              <div class="bg-white px-3 py-2">
                <p class="text-[10px] text-gray-400 font-medium">Plazo hasta el 15 de julio</p>
              </div>
            </div>

            <div class="break-inside-avoid rounded-2xl overflow-hidden shadow-sm border border-white/50 cursor-default">
              <div class="h-24 bg-gradient-to-br from-amber-400 via-orange-400 to-orange-500/80
                          flex flex-col justify-end p-3">
                <span class="text-[8px] font-black uppercase tracking-widest text-white/70 mb-1">
                  Formación
                </span>
                <p class="text-white font-black text-xs leading-snug">
                  Taller de retos con IA · 10 de julio
                </p>
              </div>
              <div class="bg-white px-3 py-2">
                <p class="text-[10px] text-gray-400 font-medium">Plazas limitadas</p>
              </div>
            </div>

            <div class="break-inside-avoid rounded-2xl overflow-hidden shadow-sm border border-white/50 cursor-default">
              <div class="h-32 bg-gradient-to-br from-[#1F2937] via-gray-700 to-gray-600
                          flex flex-col justify-end p-3">
                <span class="text-[8px] font-black uppercase tracking-widest text-white/70 mb-1">
                  Recurso
                </span>
                <p class="text-white font-black text-xs leading-snug">
                  Nueva guía de microproyectos disponible
                </p>
              </div>
              <div class="bg-white px-3 py-2">
                <p class="text-[10px] text-gray-400 font-medium">Biblioteca de recursos</p>
              </div>
            </div>

            <div class="break-inside-avoid rounded-2xl overflow-hidden shadow-sm border border-white/50 cursor-default">
              <div class="h-48 bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-600
                          flex flex-col justify-end p-3">
                <span class="text-[8px] font-black uppercase tracking-widest text-white/70 mb-1">
                  DuaLab
                </span>
                <p class="text-white font-black text-xs leading-snug">
                  Actualización de la plataforma · Nuevas funciones
                </p>
              </div>
              <div class="bg-white px-3 py-2">
                <p class="text-[10px] text-gray-400 font-medium">Hace 2 semanas</p>
              </div>
            </div>

            <div class="break-inside-avoid rounded-2xl overflow-hidden shadow-sm border border-white/50 cursor-default">
              <div class="h-28 bg-gradient-to-br from-[#99CC33] via-[#6EA820] to-[#00A859]/80
                          flex flex-col justify-end p-3">
                <span class="text-[8px] font-black uppercase tracking-widest text-white/70 mb-1">
                  Comunidad
                </span>
                <p class="text-white font-black text-xs leading-snug">
                  Proyectos destacados del trimestre
                </p>
              </div>
              <div class="bg-white px-3 py-2">
                <p class="text-[10px] text-gray-400 font-medium">Ver selección →</p>
              </div>
            </div>

          </div>
        </div>

        <!-- Eventos con calendario editable -->
        <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
          <div class="bg-[#374151] px-5 py-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-xl bg-[#00A859]/20 border border-[#00A859]/25
                          flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
              </div>
              <h3 class="text-white font-black text-sm capitalize">{{ calendarMonthLabel }}</h3>
            </div>
            <div class="flex items-center gap-1">
              <button @click="prevMonth"
                      class="w-6 h-6 rounded-lg flex items-center justify-center
                             text-white/60 hover:text-white hover:bg-white/10 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                </svg>
              </button>
              <button @click="nextMonth"
                      class="w-6 h-6 rounded-lg flex items-center justify-center
                             text-white/60 hover:text-white hover:bg-white/10 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Grid del calendario -->
          <div class="px-3 pt-3 pb-1">
            <div class="grid grid-cols-7 mb-1">
              <div v-for="d in ['L','M','X','J','V','S','D']" :key="d"
                   class="text-center text-[9px] font-black uppercase tracking-widest text-gray-300 pb-1">
                {{ d }}
              </div>
            </div>
            <div class="grid grid-cols-7 gap-px">
              <div v-for="(day, i) in calendarDays" :key="i"
                   class="min-h-[52px] flex flex-col rounded-lg overflow-hidden"
                   :class="day ? 'cursor-pointer hover:bg-gray-50 transition-colors' : ''"
                   @click="selectCalDay(day)">
                <div v-if="day" class="px-1 pt-1">
                  <span :class="[
                    'w-5 h-5 flex items-center justify-center rounded-full text-[10px] font-bold',
                    isToday(day) ? 'bg-[#00A859] text-white' : '',
                    isDaySelected(day) ? 'ring-2 ring-[#00A859] ring-offset-1' : '',
                    !isToday(day) ? 'text-gray-600' : ''
                  ]">{{ day }}</span>
                </div>
                <!-- Bloques de eventos -->
                <div v-if="day" class="flex-1 px-0.5 pb-0.5 space-y-px mt-0.5">
                  <div v-for="(ev, ei) in dayEvents(day)" :key="ei"
                       class="rounded px-1 py-px text-[8px] font-bold text-white leading-tight truncate"
                       :style="{ backgroundColor: ev.color }">
                    {{ ev.text }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Formulario añadir evento (si hay día seleccionado) -->
          <div v-if="selectedDate" class="mx-3 mb-3 mt-2 p-3 rounded-xl bg-gray-50 border border-gray-100">
            <p class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">
              Evento el día {{ selectedDate.day }}
            </p>
            <input v-model="newEventText"
                   type="text"
                   placeholder="Nombre del evento…"
                   @keyup.enter="addEvento"
                   class="w-full text-xs font-medium text-[#1F2937] bg-white border border-gray-200
                          rounded-lg px-3 py-2 mb-2 outline-none focus:border-[#00A859] transition-colors"/>
            <div class="flex items-center gap-2 mb-2">
              <button v-for="c in eventColors" :key="c"
                      @click="newEventColor = c"
                      class="w-5 h-5 rounded-full border-2 transition-all"
                      :style="{ backgroundColor: c }"
                      :class="newEventColor === c ? 'border-[#1F2937] scale-110' : 'border-transparent'">
              </button>
            </div>
            <div class="flex gap-2">
              <button @click="addEvento"
                      class="flex-1 text-[10px] font-black uppercase tracking-widest text-white
                             bg-[#00A859] hover:bg-[#007a40] rounded-lg py-1.5 transition-colors">
                Añadir
              </button>
              <button @click="selectedDate = null"
                      class="text-[10px] font-black uppercase tracking-widest text-gray-400
                             hover:text-gray-600 px-3 transition-colors">
                Cancelar
              </button>
            </div>
          </div>

          <!-- Lista de eventos del mes -->
          <div class="border-t border-gray-100 px-4 py-3">
            <p class="text-[9px] font-black uppercase tracking-widest text-gray-300 mb-2">
              Este mes
            </p>
            <div v-if="eventosDelMes.length === 0"
                 class="text-[10px] text-gray-300 font-medium text-center py-2">
              Sin eventos · Toca un día para añadir
            </div>
            <ul v-else class="space-y-1.5">
              <li v-for="(ev, i) in eventosDelMes" :key="i"
                  class="flex items-center gap-2 group">
                <div class="w-2.5 h-2.5 rounded shrink-0" :style="{ backgroundColor: ev.color }"></div>
                <span class="flex-1 text-xs font-medium text-[#1F2937] truncate">
                  <span class="text-gray-400 font-bold">{{ ev.day }} —</span> {{ ev.text }}
                </span>
                <button @click="removeEvento(eventos.indexOf(ev))"
                        class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-red-400
                               transition-all text-xs font-black shrink-0">
                  ×
                </button>
              </li>
            </ul>
          </div>
        </div>

      </div>

    </div>
  </div>
</template>

