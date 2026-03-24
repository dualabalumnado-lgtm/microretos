<script setup>
import { ref, computed  } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LoginModal from './LoginModal.vue'  
import api from '../api.js'
import { useAuthStore } from '../stores/auth'

const authStore    = useAuthStore()
const isOpen    = ref(false)
const logoError = ref(false)
const route     = useRoute()
const router    = useRouter()

// 👇 controla el modal
const showLogin = ref(false)
const cargandoOut  = ref(false) 

// const estaAutenticado = computed(() => !!localStorage.getItem('admin_token'))

const toggle = () => { isOpen.value = !isOpen.value }
const close  = () => { isOpen.value = false }

const closeOnMobile = () => {
  if (window.innerWidth < 1024) isOpen.value = false
}

const isActive = (path) =>
  path === '/' ? route.path === '/' : route.path.startsWith(path)

// 👇 intercepta el click del generador
const irAGenerador = () => {
  closeOnMobile()
  if (authStore.isAuthenticated) {
  // if (localStorage.getItem('admin_token')) {
    router.push('/microretos')
  } else {
    showLogin.value = true
  }
}

// 👇 navega tras login exitoso
const onLoginSuccess = () => {
  isOpen.value = false
  router.push('/microretos')
}

const cerrarSesion = async () => {
  cargandoOut.value = true
  try {
    await api.post('/admin/logout')
  } catch (e) {
    // Si falla la petición, igual limpiamos localmente
    console.warn('Error al revocar token en servidor:', e)
  } finally {
    authStore.logout()
    // localStorage.removeItem('admin_token')
    cargandoOut.value = false
    isOpen.value = false
    router.push('/')
  }
}


defineExpose({ isOpen, toggle, close })
</script>

<template>
  <Transition name="sp-fade">
    <div
      v-if="isOpen"
      class="fixed inset-0 bg-black/50 z-30 lg:hidden"
      @click="close"
    />
  </Transition>

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

  <Transition name="sp-slide">
    <aside
      v-if="isOpen"
      class="fixed top-0 left-0 h-full w-64 z-40 flex flex-col
             bg-[#1F2937] border-r border-[#333333]
             shadow-[6px_0_32px_rgba(0,0,0,0.25)]"
    >
      <!-- Cabecera con logo -->
      <div class="flex items-center gap-3 px-5 pt-7 pb-5 border-b border-white/10">
        <img
          src="../assets/logo.png"
          alt="DuaLab Logo"
          class="h-9 w-auto object-contain"
          @error="logoError = true"
          v-if="!logoError"
        />
        <div v-else
             class="w-9 h-9 rounded-xl bg-[#00A859] flex items-center justify-center font-black text-white text-sm">
          D
        </div>
        <span
          class="ml-4 font-black text-xl tracking-tighter text-white uppercase"
        >
          Dua<span class="text-[#00A859]">Lab</span>
        </span>
      </div>

      <!-- Navegación -->
      <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">

        <p class="px-3 mb-3 text-[9px] font-black uppercase tracking-[0.2em] text-white/30 select-none">
          Navegación
        </p>

        <RouterLink
          to="/"
          @click="closeOnMobile"
          class="nav-item"
          :class="isActive('/') ? 'nav-item--active' : 'nav-item--idle'"
        >
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9.5L12 3l9 6.5V20a1 1 0 01-1 1H4a1 1 0 01-1-1V9.5z"/>
            <path d="M9 21V12h6v9"/>
          </svg>
          <span>Inicio</span>
          <span v-if="isActive('/')"
                class="ml-auto text-[9px] font-black uppercase tracking-widest
                       bg-[#00A859] text-white px-2 py-0.5 rounded-full">
            Aquí
          </span>
        </RouterLink>

        <div class="my-4 border-t border-white/10" />

        <p class="px-3 mb-3 text-[9px] font-black uppercase tracking-[0.2em] text-white/30 select-none">
          Herramientas
        </p>

        <button
          @click="irAGenerador"
          class="nav-item w-full text-left"
          :class="isActive('/microretos') ? 'nav-item--active' : 'nav-item--idle'"
        >
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
              stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
          <span>Generador de microretos</span>
        </button>

        <RouterLink
          to="/biblioteca"
          @click="closeOnMobile"
          class="nav-item"
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
        </RouterLink>

      </nav>

      <!-- Footer: sesión + sistema activo -->
      <div class="px-4 py-4 border-t border-white/10 space-y-3">

        <!-- ← BLOQUE DE SESIÓN -->
        <!-- Si está autenticado: muestra email + botón logout -->
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

        <!-- Si NO está autenticado: botón de login -->
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
.nav-item--idle { color: rgba(255, 255, 255, 0.5); }
.nav-item--idle:hover {
  background-color: rgba(255, 255, 255, 0.07);
  color: rgba(255, 255, 255, 0.9);
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
.sp-slide-enter-active,
.sp-slide-leave-active { transition: transform 280ms cubic-bezier(0.4, 0, 0.2, 1); }
.sp-slide-enter-from,
.sp-slide-leave-to { transform: translateX(-100%); }
.sp-fade-enter-active,
.sp-fade-leave-active { transition: opacity 250ms ease; }
.sp-fade-enter-from,
.sp-fade-leave-to { opacity: 0; }
</style>