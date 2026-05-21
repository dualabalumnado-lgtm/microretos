<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LoginModal from './LoginModal.vue'
import api from '../api.js'
import { useAuthStore, ROLE_DOCENTE, ROLE_EMPRESA } from '../stores/auth'
import { useUIState } from '../composables/useUIState.js'

const authStore = useAuthStore()
const { tourActivo, showWelcome, welcomeRole, welcomeName, triggerWelcome } = useUIState()
const isOpen    = ref(false)
const logoError = ref(false)
const route     = useRoute()
const router    = useRouter()

const showLogin        = ref(false)
const cargandoOut      = ref(false)
const destinoTrasLogin = ref('/')

// ─── Modal de información ─────────────────────────────────
const mostrarInfo = ref(false)

const toggle = () => { isOpen.value = !isOpen.value }
const close  = () => { isOpen.value = false }

const closeOnMobile = () => {
  if (window.innerWidth < 1024) isOpen.value = false
}

const isActive = (path) =>
  path === '/' ? route.path === '/' : route.path.startsWith(path)

onMounted(() => { isOpen.value = route.path !== '/' })
watch(() => route.path, () => close())

watch(
  () => route.query.redirect,
  (redirect) => {
    if (redirect && !authStore.isAuthenticated) {
      destinoTrasLogin.value = String(redirect)
      showLogin.value = true
    }
  },
  { immediate: true }
)

const irA = (ruta) => {
  closeOnMobile()
  if (authStore.isAuthenticated) {
    const yaEstoy = route.path === ruta || route.path.startsWith(ruta + '/')
    router.push(yaEstoy ? { path: ruta, query: { _t: Date.now() } } : ruta)
  } else {
    destinoTrasLogin.value = ruta
    showLogin.value = true
  }
}

const onLoginSuccess = (data) => {
  const role    = data?.role ?? authStore.userRole
  const destino = destinoTrasLogin.value || rolHome(role)
  destinoTrasLogin.value = '/'
  isOpen.value = false
  // Esperar a que la navegación final termine (incluyendo posibles redirects
  // del guard de roles) antes de mostrar el toast, para evitar que una
  // doble navegación lo descarte antes de que el usuario lo vea.
  router.push(destino).finally(() => triggerWelcome(role, authStore.userName))
}

function rolHome(role) {
  if (role === ROLE_DOCENTE) return '/biblioteca'
  if (role === ROLE_EMPRESA) return '/microretos'
  return '/microretos'
}

const cerrarSesion = async () => {
  cargandoOut.value = true
  try {
    await api.post('/admin/logout')
  } catch (e) {
    console.warn('Error al revocar token en servidor:', e)
  } finally {
    authStore.logout()
    cargandoOut.value = false
    isOpen.value = false
    router.push('/')
  }
}

defineExpose({ isOpen, toggle, close })
</script>

<template>
  <!-- Overlay móvil -->
  <Transition name="sp-fade">
    <div
      v-if="isOpen"
      class="fixed inset-0 bg-black/50 lg:bg-transparent z-30"
      @click="close"
    />
  </Transition>

  <!-- Botón hamburguesa (oculto durante el tour) -->
  <button
    v-show="!tourActivo"
    @click="toggle"
    :aria-label="isOpen ? 'Cerrar menú' : 'Abrir menú'"
    :aria-expanded="isOpen"
    class="fixed top-5 left-5 z-50 w-11 h-11 rounded-2xl
           bg-[#1F2937] border border-[#333333] shadow-lg
           flex items-center justify-center
           hover:border-[#00A859] hover:shadow-[0_0_0_3px_rgba(0,168,89,0.2)]
           transition-all duration-200"
  >
    <span class="flex flex-col gap-[5px] w-[18px]">
      <span class="block h-[2px] rounded-full bg-white transition-all duration-300 origin-center"
            :class="isOpen ? 'rotate-45 translate-y-[7px]' : ''" />
      <span class="block h-[2px] rounded-full bg-white transition-all duration-300"
            :class="isOpen ? 'opacity-0 scale-x-0' : ''" />
      <span class="block h-[2px] rounded-full bg-white transition-all duration-300 origin-center"
            :class="isOpen ? '-rotate-45 -translate-y-[7px]' : ''" />
    </span>
  </button>

  <!-- Panel lateral (también oculto durante el tour) -->
  <Transition name="sp-slide">
    <aside
      v-if="isOpen && !tourActivo"
      class="fixed top-12 left-0 h-[calc(100vh-3rem)] w-64 max-w-[85vw] z-40 flex flex-col
             bg-[#1F2937] border-r border-[#333333]
             shadow-[6px_0_32px_rgba(0,0,0,0.25)]"
    >
      <!-- ── Cabecera: DuaLab → home ── -->
      <RouterLink
        to="/"
        @click="close"
        class="flex items-center gap-3 px-5 pt-4 pb-4 border-b border-white/10
               hover:opacity-80 transition-opacity duration-150 shrink-0"
      >
        <img
          src="../assets/logo.png"
          alt="DuaLab Logo"
          class="h-9 w-auto object-contain"
          @error="logoError = true"
          v-if="!logoError"
        />
        <div v-else
             class="w-9 h-9 rounded-xl bg-[#00A859] flex items-center justify-center font-black text-white text-sm shrink-0">
          D
        </div>
        <span class="ml-4 font-black text-xl tracking-tighter text-white uppercase select-none">
          Dua<span class="text-[#00A859]">Lab</span>
        </span>
      </RouterLink>

      <!-- ── Navegación ── -->
      <nav class="flex-1 min-h-0 px-3 py-3 space-y-1 overflow-y-auto overscroll-contain">

        <!-- ═══ GRUPO: MICRORETOS + TALLER DE IDEAS ═══ -->
        <div
          v-if="authStore.canAccess('microretos') || authStore.canAccess('biblioteca') || authStore.canAccess('dashboard-docente') || authStore.canAccess('startup-day')"
          class="rounded-2xl border border-[#00A859]/20 bg-[#00A859]/5 px-2 pt-2 pb-2 space-y-1"
        >

          <!-- FASE 1: MICRORETOS -->
          <div
            v-if="authStore.canAccess('microretos') || authStore.canAccess('biblioteca')"
            class="group/tip relative"
          >
            <div class="w-full flex items-center gap-2 px-3 mb-1
                     text-[9px] font-black uppercase tracking-[0.2em]
                     text-[#00A859]/70 select-none">
              <span class="flex-1 text-left flex items-center gap-1.5">
                <span class="inline-flex items-center justify-center w-4 h-4 rounded-full
                             bg-[#00A859]/20 text-[#00A859] text-[8px] font-black shrink-0">1</span>
                Retos
              </span>
            </div>
            <div class="sp-tooltip">Fase 1 — Crea retos con IA y compártelos con el alumnado<div class="sp-tooltip-arrow"/></div>
          </div>

          <div class="space-y-0.5">

              <!-- Generador de microretos -->
              <div v-if="authStore.canAccess('microretos')" class="group/tip relative">
                <button
                  @click="irA('/microretos')"
                  title="Genera retos con IA a partir de una empresa y los criterios del ciclo"
                  class="nav-item w-full text-left"
                  :class="isActive('/microretos') ? 'nav-item--active' : 'nav-item--idle'"
                >
                  <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                      stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                  </svg>
                  <span>Generador</span>
                </button>
                <div class="sp-tooltip">Genera retos con IA a partir de una empresa y los criterios del ciclo<div class="sp-tooltip-arrow"/></div>
              </div>

              <!-- Biblioteca de microretos -->
              <div v-if="authStore.canAccess('biblioteca')" class="group/tip relative">
                <button
                  @click="irA('/biblioteca')"
                  title="Consulta todos los retos guardados y comparte el QR con el alumnado"
                  class="nav-item w-full text-left"
                  :class="isActive('/biblioteca') ? 'nav-item--active' : 'nav-item--idle'"
                >
                  <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                       stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 22v-15A2.5 2.5 0 016.5 2z"/>
                    <line x1="9" y1="7" x2="15" y2="7"/>
                    <line x1="9" y1="11" x2="15" y2="11"/>
                  </svg>
                  <span>Biblioteca</span>
                </button>
                <div class="sp-tooltip">Consulta todos los retos guardados y comparte el QR con el alumnado<div class="sp-tooltip-arrow"/></div>
              </div>

          </div>

          <!-- Separador FASE 2 solo si hay items de ambas fases -->
          <div
            v-if="(authStore.canAccess('microretos') || authStore.canAccess('biblioteca')) && (authStore.canAccess('dashboard-docente') || authStore.canAccess('startup-day'))"
            class="border-t border-[#00A859]/15 mx-1 my-1"
          />

          <!-- FASE 2: TALLER DE IDEAS -->
          <div
            v-if="authStore.canAccess('dashboard-docente') || authStore.canAccess('startup-day')"
            class="group/tip relative"
          >
            <div class="w-full flex items-center gap-2 px-3 mb-1
                     text-[9px] font-black uppercase tracking-[0.2em]
                     text-[#00A859]/70 select-none">
              <span class="flex-1 text-left flex items-center gap-1.5">
                <span class="inline-flex items-center justify-center w-4 h-4 rounded-full
                             bg-[#00A859]/20 text-[#00A859] text-[8px] font-black shrink-0">2</span>
                Taller de Ideas
              </span>
            </div>
            <div class="sp-tooltip">Fase 2 — Registra sesiones, crea proyectos y gestiona el Taller de Ideas<div class="sp-tooltip-arrow"/></div>
          </div>

          <div class="space-y-0.5">

              <!-- Dashboard docentes -->
              <div v-if="authStore.canAccess('dashboard-docente')" class="group/tip relative">
                <button
                  @click="irA('/dashboard')"
                  title="Registra sesiones de trabajo con retos"
                  class="nav-item w-full text-left"
                  :class="isActive('/dashboard') ? 'nav-item--active' : 'nav-item--idle'"
                >
                  <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                       stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                    <rect x="9" y="3" width="6" height="4" rx="1"/>
                    <path d="M9 12l2 2 4-4"/>
                  </svg>
                  <span>Dashboard docente</span>
                </button>
                <div class="sp-tooltip">Registra sesiones de trabajo con retos<div class="sp-tooltip-arrow"/></div>
              </div>

              <!-- Microproyectos -->
              <div v-if="authStore.canAccess('startup-day')" class="group/tip relative">
                <button
                  @click="irA('/startup-day')"
                  title="Crea y gestiona proyectos para el Taller de Ideas"
                  class="nav-item w-full text-left"
                  :class="$route.path.startsWith('/startup-day') ? 'nav-item--active' : 'nav-item--idle'"
                >
                  <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                       stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                  </svg>
                  <span>Proyectos</span>
                </button>
                <div class="sp-tooltip">Crea y gestiona proyectos para el Taller de Ideas<div class="sp-tooltip-arrow"/></div>
              </div>

          </div>

        </div>

        <!-- ═══════════════ EMPRESAS ════════════════════ -->
        <template v-if="authStore.canAccess('empresas')">
          <div class="my-2 border-t border-white/10" />

          <div class="group/tip relative">
            <div class="w-full flex items-center gap-2 px-3 mb-1
                     text-[9px] font-black uppercase tracking-[0.2em]
                     text-white/40 select-none">
              <span class="flex-1 text-left flex items-center gap-1.5">
                <span class="inline-flex items-center justify-center w-4 h-4 rounded-full
                             bg-blue-400/20 text-blue-400 text-[8px] font-black shrink-0">E</span>
                Empresas
                <!-- Candado: indica que requiere contraseña especial -->
                <svg class="w-3 h-3 text-white/30 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke-width="2"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
              </span>
            </div>
            <div class="sp-tooltip">Módulo Empresas — Contacto directo y envío de enlaces de validación (requiere contraseña especial)<div class="sp-tooltip-arrow"/></div>
          </div>

          <div class="space-y-0.5">
              <div class="group/tip relative">
                <button
                  @click="irA('/empresas')"
                  title="Consulta y contacta con las empresas de la base de datos (requiere contraseña especial)"
                  class="nav-item w-full text-left"
                  :class="isActive('/empresas') ? 'nav-item--active' : 'nav-item--idle'"
                >
                  <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                       stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                  </svg>
                  <span>Directorio empresas</span>
                </button>
                <div class="sp-tooltip">Consulta y contacta con las empresas de la base de datos<div class="sp-tooltip-arrow"/></div>
              </div>
          </div>
        </template>

        <!-- ═══════════════ ADMINISTRACIÓN ═════════════ -->
        <template v-if="authStore.canAccess('base-datos') || authStore.canAccess('papelera') || authStore.canAccess('gestion-usuarios')">
          <div class="my-2 border-t border-white/10" />

          <div class="group/tip relative">
            <div class="w-full flex items-center gap-2 px-3 mb-1
                     text-[9px] font-black uppercase tracking-[0.2em]
                     text-white/40 select-none">
              <span class="flex-1 text-left">Administración</span>
            </div>
            <div class="sp-tooltip">Gestión de datos de la plataforma<div class="sp-tooltip-arrow"/></div>
          </div>

          <div class="space-y-0.5">

              <!-- Gestión de usuarios -->
              <div v-if="authStore.canAccess('gestion-usuarios')" class="group/tip relative">
                <button
                  @click="irA('/admin/usuarios')"
                  title="Gestiona las cuentas de docentes y empresas"
                  class="nav-item w-full text-left"
                  :class="isActive('/admin/usuarios') ? 'nav-item--active' : 'nav-item--idle'"
                >
                  <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                       stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                  </svg>
                  <span>Usuarios</span>
                </button>
                <div class="sp-tooltip">Gestiona las cuentas de docentes y empresas<div class="sp-tooltip-arrow"/></div>
              </div>

              <!-- Base de datos -->
              <div v-if="authStore.canAccess('base-datos')" class="group/tip relative">
                <button
                  @click="irA('/base-datos')"
                  title="Empresas, centros educativos, familias y ciclos del ecosistema DuaLab"
                  class="nav-item w-full text-left"
                  :class="isActive('/base-datos') ? 'nav-item--active' : 'nav-item--idle'"
                >
                  <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                       stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/>
                    <path d="M21 12c0 1.657-4.03 3-9 3S3 13.657 3 12"/>
                    <path d="M3 5v14c0 1.657 4.03 3 9 3s9-1.343 9-3V5"/>
                  </svg>
                  <span>Base de datos</span>
                </button>
                <div class="sp-tooltip">Empresas, centros educativos, familias y ciclos del ecosistema DuaLab<div class="sp-tooltip-arrow"/></div>
              </div>

              <!-- Papelera -->
              <div v-if="authStore.canAccess('papelera')" class="group/tip relative">
                <button
                  @click="irA('/papelera')"
                  title="Elementos eliminados — restáuralos o bórralos definitivamente"
                  class="nav-item w-full text-left"
                  :class="isActive('/papelera') ? 'nav-item--active' : 'nav-item--idle'"
                >
                  <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                       stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                    <path d="M10 11v6M14 11v6"/>
                    <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                  </svg>
                  <span>Papelera</span>
                </button>
                <div class="sp-tooltip">Elementos eliminados — restáuralos o bórralos definitivamente<div class="sp-tooltip-arrow"/></div>
              </div>

          </div>
        </template>

      </nav>

      <!-- ── Footer: sesión + info + sistema ── -->
      <div class="px-4 py-3 border-t border-white/10 space-y-2 shrink-0">

        <!-- Botón de información -->
        <button
          @click="mostrarInfo = true"
          class="w-full flex items-center gap-2 px-3 py-2 rounded-xl
                 bg-white/5 border border-white/10 text-white/40
                 hover:text-white/70 hover:bg-white/8 hover:border-white/20
                 font-bold text-[10px] uppercase tracking-widest
                 transition-all duration-150"
        >
          <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          ¿Qué es DuaLab?
        </button>

        <!-- Sesión activa -->
        <div v-if="authStore.isAuthenticated" class="px-3 py-2 rounded-2xl bg-white/5 border border-white/10 space-y-2">
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-full bg-[#00A859]/20 border border-[#00A859]/30 flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-[9px] font-black uppercase tracking-widest text-white/30">Sesión activa</p>
              <p class="text-xs font-bold text-white truncate">{{ authStore.roleLabel }}</p>
              <p class="text-[10px] text-white/30 truncate">{{ authStore.userName }}</p>
            </div>
          </div>

          <!-- Aviso de expiración inminente -->
          <Transition name="sp-fade">
            <div v-if="authStore.minutosRestantes >= 0 && authStore.minutosRestantes <= 120"
              class="rounded-xl px-3 py-2 text-[10px] font-bold space-y-2"
              :class="authStore.minutosRestantes <= 30
                ? 'bg-red-500/15 border border-red-500/30 text-red-300'
                : 'bg-amber-500/15 border border-amber-500/30 text-amber-300'">
              <div class="flex items-center gap-1.5">
                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>
                  {{ authStore.minutosRestantes <= 0
                    ? 'Sesión a punto de expirar'
                    : authStore.minutosRestantes >= 60
                      ? `Sesión expira en ${Math.floor(authStore.minutosRestantes / 60)}h ${authStore.minutosRestantes % 60}min`
                      : `Sesión expira en ${authStore.minutosRestantes} min` }}
                </span>
              </div>
              <button
                @click="authStore.refresh()"
                :disabled="authStore.refreshing"
                class="w-full py-1.5 rounded-lg font-black text-[9px] uppercase tracking-widest
                       transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                :class="authStore.minutosRestantes <= 30
                  ? 'bg-red-500/20 hover:bg-red-500/30 text-red-200'
                  : 'bg-amber-500/20 hover:bg-amber-500/30 text-amber-200'">
                {{ authStore.refreshing ? 'Renovando...' : 'Extender sesión' }}
              </button>
            </div>
          </Transition>

          <button
            @click="cerrarSesion"
            :disabled="cargandoOut"
            class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-xl
                   bg-red-500/10 border border-red-500/20 text-red-400
                   hover:bg-red-500/20 hover:border-red-500/40 hover:text-red-300
                   font-black text-[10px] uppercase tracking-widest
                   transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="!cargandoOut" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <svg v-else class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24">
              <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
            </svg>
            {{ cargandoOut ? 'Cerrando...' : 'Cerrar sesión' }}
          </button>
        </div>

        <!-- Sin sesión -->
        <button
          v-else
          @click="showLogin = true"
          class="w-full flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl
                 bg-[#00A859]/10 border border-[#00A859]/20 text-[#00A859]
                 hover:bg-[#00A859]/20 hover:border-[#00A859]/40
                 font-black text-[10px] uppercase tracking-widest
                 transition-all duration-200"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
          </svg>
          Iniciar sesión
        </button>

        <!-- Indicador sistema activo -->
        <div class="flex items-center gap-2 px-3 py-2 rounded-2xl
                    bg-[#99CC33]/10 border border-[#99CC33]/20">
          <span class="w-1.5 h-1.5 rounded-full bg-[#99CC33] animate-pulse flex-shrink-0" />
          <span class="text-[10px] font-black uppercase tracking-widest text-[#99CC33]">
            Sistema activo
          </span>
        </div>
      </div>

    </aside>
  </Transition>

  <!-- ══════════════ MODAL: ¿QUÉ ES DUALAB? ══════════════════ -->
  <Transition name="sp-fade">
    <div
      v-if="mostrarInfo"
      class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
      @click.self="mostrarInfo = false"
    >
      <div class="relative bg-[#1a2332] border border-white/10 rounded-[2rem]
                  shadow-2xl max-w-lg w-full p-8 text-white overflow-y-auto max-h-[90vh]">

        <!-- X -->
        <button
          @click="mostrarInfo = false"
          class="absolute top-4 right-4 w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10
                 flex items-center justify-center text-white/40 hover:text-white transition-all"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>

        <!-- Logo -->
        <div class="flex items-center gap-3 mb-6">
          <div class="w-12 h-12 rounded-2xl bg-[#00A859]/15 border border-[#00A859]/30
                      flex items-center justify-center">
            <svg class="w-6 h-6 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
          </div>
          <div>
            <h2 class="text-2xl font-black tracking-tight">
              Dua<span class="text-[#00A859]">Lab</span>
            </h2>
            <p class="text-white/40 text-xs font-medium">Plataforma de retos para FP Dual</p>
          </div>
        </div>

        <p class="text-white/60 text-sm leading-relaxed mb-6">
          DuaLab es la plataforma que conecta centros educativos de FP con empresas para
          generar retos de aprendizaje real, alineados con los módulos y resultados de aprendizaje del ciclo.
        </p>

        <!-- Secciones explicadas -->
        <div class="space-y-4">

          <div class="rounded-2xl bg-white/5 border border-white/8 p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-[#99CC33] mb-2">Retos</p>
            <div class="space-y-2 text-xs text-white/60">
              <p><span class="text-white font-bold">Generador</span> — Crea retos con IA a partir de los datos de una empresa y los criterios de evaluación del ciclo. Requiere sesión.</p>
              <p><span class="text-white font-bold">Biblioteca</span> — Accede a todos los retos generados. Comparte cada reto con el alumnado mediante un código QR temporal.</p>
            </div>
          </div>

          <div class="rounded-2xl bg-white/5 border border-white/8 p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-amber-400 mb-2">Taller de Ideas</p>
            <p class="text-xs text-white/60"><span class="text-white font-bold">Microproyectos</span> — Diseña y gestiona proyectos Taller de Ideas equipo, módulos, objetivos y validación por empresa.</p>
          </div>

          <div class="rounded-2xl bg-white/5 border border-white/8 p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-2">Herramientas</p>
            <div class="space-y-2 text-xs text-white/60">
              <p><span class="text-white font-bold">Dashboard docentes</span> — Panel de seguimiento del alumnado: proyectos activos, progreso y retos asignados.</p>
              <p><span class="text-white font-bold">Biblioteca retos</span> — Acceso directo a la colección completa de retos para gestión docente.</p>
              <p><span class="text-white font-bold">Base de datos</span> — Gestión de empresas, centros educativos, familias profesionales y ciclos formativos.</p>
            </div>
          </div>

        </div>

        <button
          @click="mostrarInfo = false"
          class="mt-6 w-full py-3 rounded-xl bg-[#00A859] text-white
                 font-black text-xs uppercase tracking-widest
                 hover:bg-[#009950] transition-all"
        >
          Entendido
        </button>
      </div>
    </div>
  </Transition>

  <LoginModal v-model="showLogin" @login-success="onLoginSuccess" />

  <!-- ── Modal de bienvenida por rol ── -->
  <Transition name="welcome-overlay">
    <div
      v-if="showWelcome"
      class="fixed inset-0 z-[10000] flex items-center justify-center p-6
             bg-black/75 backdrop-blur-sm"
      @click.self="showWelcome = false"
    >
      <Transition name="welcome-card" appear>
        <div
          v-if="showWelcome"
          class="relative rounded-3xl p-10 max-w-sm w-full text-center
                 bg-[#1F2937] border border-[#333333]
                 shadow-[0_24px_64px_rgba(0,0,0,0.7)]"
        >
          <!-- Icono -->
          <div class="mx-auto mb-6 w-16 h-16 rounded-2xl
                      bg-[#00A859]/15 border border-[#00A859]/30
                      flex items-center justify-center">
            <svg class="w-8 h-8 text-[#00A859]" fill="none" stroke="currentColor"
                 viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
              <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
          </div>

          <!-- Etiqueta -->
          <p class="text-[#00A859] text-[10px] font-black uppercase tracking-[0.2em] mb-4">
            Sesión iniciada
          </p>

          <!-- Mensaje principal -->
          <h2 class="text-white text-xl font-bold leading-snug">
            ¡Te damos la bienvenida<br>a DuaLab para
          </h2>
          <p class="text-[#00A859] text-4xl font-black tracking-tight mt-2 mb-1">
            {{ welcomeRole === ROLE_DOCENTE ? 'docentes' : welcomeRole === ROLE_EMPRESA ? 'empresas' : 'admin' }}
          </p>
          <p class="text-white text-xl font-bold">!</p>

          <!-- Nombre de usuario -->
          <p v-if="welcomeName" class="mt-3 text-white/50 text-sm">
            {{ welcomeName }}
          </p>

          <!-- Separador -->
          <div class="mt-8 border-t border-[#333333]" />

          <!-- Botón cerrar -->
          <button
            @click="showWelcome = false"
            class="mt-6 w-full py-3 rounded-xl bg-[#00A859] text-white
                   font-black text-xs uppercase tracking-widest
                   hover:bg-[#009950] transition-colors duration-200"
          >
            Continuar
          </button>

          <!-- X esquina -->
          <button
            @click="showWelcome = false"
            class="absolute top-4 right-4 w-8 h-8 rounded-lg bg-white/5
                   hover:bg-white/10 flex items-center justify-center
                   text-white/40 hover:text-white transition-all"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<style scoped>
.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 1rem;
  font-size: 0.8125rem;
  font-weight: 700;
  text-decoration: none;
  transition: background-color 150ms ease, color 150ms ease;
  cursor: pointer;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.nav-item--idle   { color: rgba(255,255,255,0.5); }
.nav-item--idle:hover {
  background-color: rgba(255,255,255,0.07);
  color: rgba(255,255,255,0.9);
}
.nav-item--active {
  background: linear-gradient(135deg, rgba(0,168,89,0.18) 0%, rgba(153,204,51,0.12) 100%);
  color: #00A859;
  box-shadow: inset 3px 0 0 #00A859;
}
.nav-icon {
  width: 17px;
  height: 17px;
  flex-shrink: 0;
  color: inherit;
}

/* Tooltips deshabilitados: el nav usa overflow-y:auto (scroll)
   que crea un scroll container y recorta los children absolutos */
.sp-tooltip       { display: none; }
.sp-tooltip-arrow { display: none; }

/* Scrollbar discreta para el nav */
nav::-webkit-scrollbar        { width: 3px; }
nav::-webkit-scrollbar-track  { background: transparent; }
nav::-webkit-scrollbar-thumb  { background: rgba(255,255,255,0.12); border-radius: 99px; }
nav::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.25); }

/* ─── Panel slide / fade ─────────────────────────────────────── */
.sp-slide-enter-active,
.sp-slide-leave-active { transition: transform 280ms cubic-bezier(0.4, 0, 0.2, 1); }
.sp-slide-enter-from,
.sp-slide-leave-to     { transform: translateX(-100%); }
.sp-fade-enter-active,
.sp-fade-leave-active  { transition: opacity 250ms ease; }
.sp-fade-enter-from,
.sp-fade-leave-to      { opacity: 0; }

/* Modal de bienvenida — overlay */
.welcome-overlay-enter-active,
.welcome-overlay-leave-active { transition: opacity 0.25s ease; }
.welcome-overlay-enter-from,
.welcome-overlay-leave-to     { opacity: 0; }

/* Modal de bienvenida — tarjeta */
.welcome-card-enter-active { transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.welcome-card-leave-active { transition: all 0.2s ease; }
.welcome-card-enter-from   { opacity: 0; transform: scale(0.85) translateY(24px); }
.welcome-card-leave-to     { opacity: 0; transform: scale(0.95) translateY(8px); }
</style>
