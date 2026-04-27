<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LoginModal from './LoginModal.vue'
import api from '../api.js'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const isOpen    = ref(false)
const logoError = ref(false)
const route     = useRoute()
const router    = useRouter()

const showLogin        = ref(false)
const cargandoOut      = ref(false)
const destinoTrasLogin = ref('/microretos')

// ─── Secciones colapsables ────────────────────────────────
const microretos_abierto   = ref(true)
const herramientas_abierto = ref(true)

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
    router.push(ruta)
  } else {
    destinoTrasLogin.value = ruta
    showLogin.value = true
  }
}

const onLoginSuccess = () => {
  const destino = destinoTrasLogin.value || '/'
  isOpen.value = false
  router.push(destino)
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

  <!-- Botón hamburguesa -->
  <button
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

  <!-- Panel lateral -->
  <Transition name="sp-slide">
    <aside
      v-if="isOpen"
      class="fixed top-0 left-0 h-full w-64 z-40 flex flex-col overflow-visible
             bg-[#1F2937] border-r border-[#333333]
             shadow-[6px_0_32px_rgba(0,0,0,0.25)]"
    >
      <!-- ── Cabecera: DuaLab → home ── -->
      <RouterLink
        to="/"
        @click="close"
        class="flex items-center gap-3 px-5 pt-7 pb-5 border-b border-white/10
               hover:opacity-80 transition-opacity duration-150"
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
      <nav class="flex-1 px-3 py-5 space-y-1 overflow-visible">

        <!-- ═══════════════ MICRORETOS (colapsable) ═══════════════ -->
        <div class="group/tip relative">
          <button
            @click="microretos_abierto = !microretos_abierto"
            class="w-full flex items-center gap-2 px-3 mb-1
                   text-[9px] font-black uppercase tracking-[0.2em]
                   text-white/40 hover:text-white/60 transition-colors duration-150 select-none"
          >
            <span class="flex-1 text-left">Microretos</span>
            <svg
              class="w-3 h-3 transition-transform duration-200"
              :class="microretos_abierto ? 'rotate-180' : ''"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div class="sp-tooltip">Crea y consulta microretos de FP Dual con IA<div class="sp-tooltip-arrow"/></div>
        </div>

        <Transition name="sp-collapse">
          <div v-if="microretos_abierto" class="space-y-0.5">

            <!-- Generador de microretos -->
            <div class="group/tip relative">
              <button
                @click="irA('/microretos')"
                class="nav-item w-full text-left"
                :class="isActive('/microretos') ? 'nav-item--active' : 'nav-item--idle'"
              >
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span>Generador</span>
              </button>
              <div class="sp-tooltip">Genera microretos con IA a partir de una empresa y los criterios del ciclo<div class="sp-tooltip-arrow"/></div>
            </div>

            <!-- Biblioteca de microretos -->
            <div class="group/tip relative">
              <button
                @click="irA('/biblioteca')"
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
                <span>Biblioteca de microretos</span>
              </button>
              <div class="sp-tooltip">Consulta todos los microretos guardados y comparte el QR con el alumnado<div class="sp-tooltip-arrow"/></div>
            </div>

          </div>
        </Transition>

        <div class="my-4 border-t border-white/10" />

        <!-- ═══════════════ STARTUP ═══════════════════════════════ -->
        <div class="group/tip relative">
          <p class="px-3 mb-1 text-[9px] font-black uppercase tracking-[0.2em] text-white/40 select-none cursor-default">
            Startup
          </p>
          <div class="sp-tooltip">Módulo para empresas y startups colaboradoras — en desarrollo<div class="sp-tooltip-arrow"/></div>
        </div>

        <div class="group/tip relative">
          <button
            disabled
            class="nav-item w-full text-left nav-item--idle opacity-40 cursor-not-allowed"
          >
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
              <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
              <line x1="9" y1="9" x2="9.01" y2="9"/>
              <line x1="15" y1="9" x2="15.01" y2="9"/>
            </svg>
            <span>Startup</span>
            <span class="ml-auto text-[8px] font-black uppercase tracking-widest
                         bg-white/10 text-white/40 px-2 py-0.5 rounded-full shrink-0">
              Pronto
            </span>
          </button>
          <div class="sp-tooltip">Portal de empresas colaboradoras — próximamente<div class="sp-tooltip-arrow"/></div>
        </div>

        <div class="my-4 border-t border-white/10" />

        <!-- ═══════════════ HERRAMIENTAS (colapsable) ══════════════ -->
        <div class="group/tip relative">
          <button
            @click="herramientas_abierto = !herramientas_abierto"
            class="w-full flex items-center gap-2 px-3 mb-1
                   text-[9px] font-black uppercase tracking-[0.2em]
                   text-white/40 hover:text-white/60 transition-colors duration-150 select-none"
          >
            <span class="flex-1 text-left">Herramientas</span>
            <svg
              class="w-3 h-3 transition-transform duration-200"
              :class="herramientas_abierto ? 'rotate-180' : ''"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div class="sp-tooltip">Gestión docente y datos de la plataforma<div class="sp-tooltip-arrow"/></div>
        </div>

        <Transition name="sp-collapse">
          <div v-if="herramientas_abierto" class="space-y-0.5">

            <!-- Dashboard docentes -->
            <div class="group/tip relative">
              <button
                @click="irA('/dashboard')"
                class="nav-item w-full text-left"
                :class="isActive('/dashboard') ? 'nav-item--active' : 'nav-item--idle'"
              >
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                  <rect x="9" y="3" width="6" height="4" rx="1"/>
                  <path d="M9 12l2 2 4-4"/>
                </svg>
                <span>Dashboard docentes</span>
              </button>
              <div class="sp-tooltip">Seguimiento del alumnado y gestión de proyectos activos<div class="sp-tooltip-arrow"/></div>
            </div>

            <!-- Base de datos -->
            <div class="group/tip relative">
              <button
                @click="irA('/base-datos')"
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

          </div>
        </Transition>

      </nav>

      <!-- ── Footer: sesión + info + sistema ── -->
      <div class="px-4 py-4 border-t border-white/10 space-y-3">

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
        <div v-if="authStore.isAuthenticated" class="px-3 py-3 rounded-2xl bg-white/5 border border-white/10 space-y-2">
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-full bg-[#00A859]/20 border border-[#00A859]/30 flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-[9px] font-black uppercase tracking-widest text-white/30">Sesión activa</p>
              <p class="text-xs font-bold text-white truncate">Administrador</p>
            </div>
          </div>

          <!-- Aviso de expiración inminente -->
          <Transition name="sp-fade">
            <div v-if="authStore.minutosRestantes >= 0 && authStore.minutosRestantes <= 30"
              class="rounded-xl px-3 py-2 text-[10px] font-bold space-y-2"
              :class="authStore.minutosRestantes <= 5
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
                    : `Sesión expira en ${authStore.minutosRestantes} min` }}
                </span>
              </div>
              <button
                @click="authStore.refresh()"
                :disabled="authStore.refreshing"
                class="w-full py-1.5 rounded-lg font-black text-[9px] uppercase tracking-widest
                       transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                :class="authStore.minutosRestantes <= 5
                  ? 'bg-red-500/20 hover:bg-red-500/30 text-red-200'
                  : 'bg-amber-500/20 hover:bg-amber-500/30 text-amber-200'">
                {{ authStore.refreshing ? 'Renovando...' : 'Renovar sesión' }}
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
        <div class="flex items-center gap-2 px-3 py-2.5 rounded-2xl
                    bg-[#99CC33]/10 border border-[#99CC33]/20">
          <span class="w-2 h-2 rounded-full bg-[#99CC33] animate-pulse flex-shrink-0" />
          <span class="text-xs font-black uppercase tracking-widest text-[#99CC33]">
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
            <p class="text-white/40 text-xs font-medium">Plataforma de microretos para FP Dual</p>
          </div>
        </div>

        <p class="text-white/60 text-sm leading-relaxed mb-6">
          DuaLab es la plataforma que conecta centros educativos de FP con empresas para
          generar microretos de aprendizaje real, alineados con los módulos y resultados de aprendizaje del ciclo.
        </p>

        <!-- Secciones explicadas -->
        <div class="space-y-4">

          <div class="rounded-2xl bg-white/5 border border-white/8 p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-[#99CC33] mb-2">Microretos</p>
            <div class="space-y-2 text-xs text-white/60">
              <p><span class="text-white font-bold">Generador</span> — Crea microretos con IA a partir de los datos de una empresa y los criterios de evaluación del ciclo. Requiere sesión.</p>
              <p><span class="text-white font-bold">Biblioteca</span> — Accede a todos los microretos generados. Comparte cada reto con el alumnado mediante un código QR temporal.</p>
            </div>
          </div>

          <div class="rounded-2xl bg-white/5 border border-white/8 p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-amber-400 mb-2">Startup</p>
            <p class="text-xs text-white/60">Sección en desarrollo orientada a startups y empresas colaboradoras. Próximamente disponible.</p>
          </div>

          <div class="rounded-2xl bg-white/5 border border-white/8 p-4">
            <p class="text-[10px] font-black uppercase tracking-widest text-blue-400 mb-2">Herramientas</p>
            <div class="space-y-2 text-xs text-white/60">
              <p><span class="text-white font-bold">Dashboard docentes</span> — Panel de seguimiento del alumnado: proyectos activos, progreso y microretos asignados.</p>
              <p><span class="text-white font-bold">Biblioteca microretos</span> — Acceso directo a la colección completa de retos para gestión docente.</p>
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

/* ─── Tooltip que aparece a la derecha del sidebar ─────────── */
.sp-tooltip {
  pointer-events: none;
  position: absolute;
  left: calc(100% + 12px);
  top: 50%;
  transform: translateY(-50%);
  width: 200px;
  padding: 8px 12px;
  background: #111827;
  color: rgba(255,255,255,0.85);
  font-size: 11px;
  font-weight: 500;
  line-height: 1.5;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.08);
  box-shadow: 0 8px 24px rgba(0,0,0,0.4);
  opacity: 0;
  transition: opacity 0.15s ease, transform 0.15s ease;
  transform: translateY(-50%) translateX(4px);
  z-index: 9999;
  white-space: normal;
}
.group\/tip:hover .sp-tooltip {
  opacity: 1;
  transform: translateY(-50%) translateX(0);
}
.sp-tooltip-arrow {
  position: absolute;
  right: 100%;
  top: 50%;
  transform: translateY(-50%);
  border: 5px solid transparent;
  border-right-color: #111827;
}

/* ─── Colapso sección ────────────────────────────────────────── */
.sp-collapse-enter-active,
.sp-collapse-leave-active { transition: all 0.22s ease; overflow: hidden; }
.sp-collapse-enter-from,
.sp-collapse-leave-to     { opacity: 0; transform: translateY(-6px); max-height: 0; }
.sp-collapse-enter-to,
.sp-collapse-leave-from   { max-height: 200px; }

/* ─── Panel slide / fade ─────────────────────────────────────── */
.sp-slide-enter-active,
.sp-slide-leave-active { transition: transform 280ms cubic-bezier(0.4, 0, 0.2, 1); }
.sp-slide-enter-from,
.sp-slide-leave-to     { transform: translateX(-100%); }
.sp-fade-enter-active,
.sp-fade-leave-active  { transition: opacity 250ms ease; }
.sp-fade-enter-from,
.sp-fade-leave-to      { opacity: 0; }
</style>
