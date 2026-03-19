<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import LoginModal from './LoginModal.vue'  

const isOpen    = ref(false)
const logoError = ref(false)
const route     = useRoute()
const router    = useRouter()

// 👇 controla el modal
const showLogin = ref(false)

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
  if (localStorage.getItem('admin_token')) {
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

defineExpose({ isOpen, toggle, close })
</script>

<template>
  <!-- Overlay (solo móvil, cierra el panel al tocar fuera) -->
  <Transition name="sp-fade">
    <div
      v-if="isOpen"
      class="fixed inset-0 bg-black/50 z-30 lg:hidden"
      @click="close"
    />
  </Transition>

  <!-- Botón hamburguesa / toggle -->
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
        <span class="font-black text-xl tracking-tighter text-white uppercase">
          Dua<span class="text-[#00A859]">Lab</span>
        </span>
      </div>

      <!-- Navegación -->
      <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">

        <p class="px-3 mb-3 text-[9px] font-black uppercase tracking-[0.2em] text-white/30 select-none">
          Navegación
        </p>

        <!-- Home -->
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

        <!-- Generador de microretos -->
        <!-- Generador de microretos — cambia RouterLink por button -->
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

        <!-- Biblioteca de microretos -->
        <RouterLink
          to="/biblioteca"
          @click="closeOnMobile"
          class="nav-item"
          :class="isActive('/biblioteca') ? 'nav-item--active' : 'nav-item--idle'"
        >
          <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
            <line x1="9" y1="7" x2="15" y2="7"/>
            <line x1="9" y1="11" x2="15" y2="11"/>
          </svg>
          <span>Biblioteca de microretos</span>
        </RouterLink>

      </nav>

      <!-- Footer -->
      <div class="px-4 py-4 border-t border-white/10">
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
   <!-- Modal de login -->
  <LoginModal v-model="showLogin" @login-success="onLoginSuccess" />
</template>

<style scoped>
/*Ítem base*/
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

/*En reposo*/
.nav-item--idle {
  color: rgba(255, 255, 255, 0.5);
}
.nav-item--idle:hover {
  background-color: rgba(255, 255, 255, 0.07);
  color: rgba(255, 255, 255, 0.9);
}

/*Activo*/
.nav-item--active {
  background: linear-gradient(135deg, rgba(0,168,89,0.18) 0%, rgba(153,204,51,0.12) 100%);
  color: #00A859;
  box-shadow: inset 3px 0 0 #00A859;
}

/*Icono*/
.nav-icon {
  width: 17px;
  height: 17px;
  flex-shrink: 0;
  color: inherit;
}

/*Transición panel*/
.sp-slide-enter-active,
.sp-slide-leave-active {
  transition: transform 280ms cubic-bezier(0.4, 0, 0.2, 1);
}
.sp-slide-enter-from,
.sp-slide-leave-to {
  transform: translateX(-100%);
}

/*Transición overlay*/
.sp-fade-enter-active,
.sp-fade-leave-active {
  transition: opacity 250ms ease;
}
.sp-fade-enter-from,
.sp-fade-leave-to {
  opacity: 0;
}
</style>