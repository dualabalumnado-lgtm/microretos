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

// ── Contadores ────────────────────────────────────────────────────────────────
const totalSesiones = computed(() => sesiones.value.length)

const proyectosValidados = computed(() =>
  proyectos.value.filter(p => p.estado === 'publicado' && p.empresa_validado === true).length
)

const proyectosPendientes = computed(() =>
  proyectos.value.filter(p => p.estado === 'publicado' && !p.empresa_validado).length
)

const primerNombre = computed(() => {
  const n = authStore.userName || ''
  return n.split(' ')[0] || n
})

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
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] font-sans text-[#1F2937] pt-12">
    <div class="max-w-5xl mx-auto px-4 sm:px-8 py-8">

      <!-- ══ Cabecera bienvenida ══════════════════════════════════════════════════ -->
      <div class="relative overflow-hidden bg-[#1F2937] rounded-2xl p-6 sm:p-8 mb-8
                  transition-all duration-700"
           :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">

        <div class="absolute top-0 right-0 w-80 h-80 bg-[#00A859]/10 rounded-full
                    translate-x-1/3 -translate-y-1/3 blur-[80px] pointer-events-none"></div>
        <div class="absolute -bottom-10 left-1/3 w-56 h-56 bg-[#99CC33]/8 rounded-full
                    blur-[60px] pointer-events-none"></div>

        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div class="inline-flex items-center gap-2 bg-[#00A859]/15 border border-[#00A859]/25
                        rounded-full px-3 py-1 mb-3">
              <span class="w-1.5 h-1.5 rounded-full bg-[#99CC33] animate-pulse shrink-0"></span>
              <span class="text-[#99CC33] text-[10px] font-black uppercase tracking-widest">Perfil docente</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
              Bienvenido/a, <span class="text-[#99CC33]">{{ primerNombre }}</span>
            </h1>
            <p class="text-white/40 text-sm mt-1.5 font-medium">Panel de control · DuaLab</p>
          </div>

          <div class="hidden sm:flex w-16 h-16 rounded-2xl bg-[#00A859]/15 border border-[#00A859]/25
                      items-center justify-center shrink-0">
            <svg class="w-8 h-8 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 14l9-5-9-5-9 5 9 5z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- ══ Seguimiento de proyectos ════════════════════════════════════════════ -->
      <section class="mb-8 transition-all duration-700 delay-150"
               :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">

        <div class="flex items-center gap-3 mb-4">
          <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 shrink-0">
            Seguimiento de proyectos
          </span>
          <div class="flex-1 h-px bg-gray-200"></div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">

          <!-- 1 · Sesiones registradas -->
          <button @click="irA('/dashboard')"
                  class="group bg-white border border-gray-100 rounded-2xl p-5 text-left
                         hover:border-[#00A859]/30 hover:shadow-md transition-all duration-200
                         relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-[#00A859]/0 to-[#00A859]/0
                        group-hover:from-[#00A859]/3 group-hover:to-[#99CC33]/2
                        transition-all duration-300"></div>
            <div class="relative z-10">
              <div class="w-10 h-10 rounded-xl bg-[#1F2937]/6 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-[#1F2937]/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                       M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
              </div>
              <p v-if="!cargandoSesiones" class="text-3xl font-black text-[#1F2937] mb-1 tabular-nums">
                {{ totalSesiones }}
              </p>
              <div v-else class="h-8 w-10 bg-gray-100 rounded-lg animate-pulse mb-1"></div>
              <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest leading-tight">
                Sesiones<br/>registradas
              </p>
            </div>
            <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
              <svg class="w-4 h-4 text-[#00A859]/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </div>
          </button>

          <!-- 2 · Proyectos validados -->
          <button @click="irAStartupFiltrado('proyecto')"
                  class="group bg-white border border-gray-100 rounded-2xl p-5 text-left
                         hover:border-[#00A859]/30 hover:shadow-md transition-all duration-200
                         relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-[#00A859]/0 to-[#00A859]/0
                        group-hover:from-[#00A859]/3 group-hover:to-[#99CC33]/2
                        transition-all duration-300"></div>
            <div class="relative z-10">
              <div class="w-10 h-10 rounded-xl bg-[#00A859]/10 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <p v-if="!cargandoProyectos" class="text-3xl font-black text-[#00A859] mb-1 tabular-nums">
                {{ proyectosValidados }}
              </p>
              <div v-else class="h-8 w-10 bg-gray-100 rounded-lg animate-pulse mb-1"></div>
              <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest leading-tight">
                Proyectos<br/>validados
              </p>
            </div>
            <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
              <svg class="w-4 h-4 text-[#00A859]/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </div>
          </button>

          <!-- 3 · Pendientes de validación -->
          <button @click="irAStartupFiltrado('propuesta')"
                  class="group bg-white border border-gray-100 rounded-2xl p-5 text-left
                         hover:border-amber-300/60 hover:shadow-md transition-all duration-200
                         relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-500/0 to-amber-500/0
                        group-hover:from-amber-500/3 group-hover:to-amber-400/2
                        transition-all duration-300"></div>
            <div class="relative z-10">
              <div class="w-10 h-10 rounded-xl bg-amber-400/10 flex items-center justify-center mb-4">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <p v-if="!cargandoProyectos" class="text-3xl font-black text-amber-500 mb-1 tabular-nums">
                {{ proyectosPendientes }}
              </p>
              <div v-else class="h-8 w-10 bg-gray-100 rounded-lg animate-pulse mb-1"></div>
              <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest leading-tight">
                Pendientes de<br/>validación
              </p>
            </div>
            <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
              <svg class="w-4 h-4 text-amber-400/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </div>
          </button>

          <!-- 4 · Alumnado asignado (futuro) -->
          <div class="relative bg-white border border-dashed border-gray-200 rounded-2xl p-5">
            <div class="absolute top-3 right-3 bg-gray-100 text-gray-400
                        text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full">
              Pronto
            </div>
            <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center mb-4">
              <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857
                     M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857
                     m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
            </div>
            <p class="text-3xl font-black text-gray-300 mb-1">—</p>
            <p class="text-[11px] font-bold text-gray-300 uppercase tracking-widest leading-tight">
              Alumnado<br/>asignado
            </p>
          </div>

        </div>
      </section>

      <!-- ══ Herramientas ════════════════════════════════════════════════════════ -->
      <section class="transition-all duration-700 delay-300"
               :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'">

        <div class="flex items-center gap-3 mb-4">
          <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 shrink-0">
            Herramientas
          </span>
          <div class="flex-1 h-px bg-gray-200"></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

          <!-- Retos -->
          <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
            <div class="bg-[#1F2937] px-5 py-4 flex items-center gap-3">
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
            <div class="bg-[#1F2937] px-5 py-4 flex items-center gap-3">
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

        </div>
      </section>

    </div>
  </div>
</template>

