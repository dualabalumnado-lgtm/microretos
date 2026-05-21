<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api.js'
import { useAuthStore } from '../stores/auth'

const emit = defineEmits(['toggle-panel'])

const authStore = useAuthStore()
const route     = useRoute()
const router    = useRouter()

const cargandoOut = ref(false)

const sectionLabels = {
  'microretos':           'Generador',
  'biblioteca':           'Biblioteca',
  'detalle-microreto':    'Reto',
  'base-datos':           'Base de datos',
  'papelera':             'Papelera',
  'empresas':             'Empresas',
  'dashboard-docente':    'Dashboard',
  'sesiones-registradas': 'Sesiones',
  'startup-day':          'Proyectos',
  'startup-day-crear':    'Nuevo proyecto',
  'startup-day-detalle':  'Proyecto',
  'startup-day-editar':   'Editar proyecto',
  'gestion-usuarios':     'Usuarios',
}

const sectionLabel = computed(() => sectionLabels[route.name] ?? null)

const minutosLabel = computed(() => {
  const m = authStore.minutosRestantes
  if (m <= 0)  return 'Expira ahora'
  if (m >= 60) return `${Math.floor(m / 60)}h ${m % 60}min`
  return `${m} min`
})

const cerrarSesion = async () => {
  cargandoOut.value = true
  try {
    await api.post('/admin/logout')
  } catch (e) {
    console.warn('Error al revocar token:', e)
  } finally {
    authStore.logout()
    cargandoOut.value = false
    router.push('/')
  }
}
</script>

<template>
  <header
    class="fixed top-0 left-0 right-0 h-12 z-50 flex items-center gap-2 px-3
           bg-[#1F2937] border-b border-[#333333] select-none"
  >
    <!-- Hamburger toggle -->
    <button
      @click="emit('toggle-panel')"
      aria-label="Abrir / cerrar menú"
      class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0
             text-white/50 hover:text-white hover:bg-white/10
             transition-all duration-150"
    >
      <span class="flex flex-col gap-[4px] w-[15px]">
        <span class="block h-[2px] rounded-full bg-current" />
        <span class="block h-[2px] rounded-full bg-current" />
        <span class="block h-[2px] rounded-full bg-current" />
      </span>
    </button>

    <!-- Section label -->
    <span
      v-if="sectionLabel"
      class="text-[11px] font-black uppercase tracking-[0.15em] text-white/50 truncate"
    >
      {{ sectionLabel }}
    </span>
    <span
      v-else
      class="font-black text-sm tracking-tighter text-white/40 uppercase select-none"
    >
      Dua<span class="text-[#00A859]/60">Lab</span>
    </span>

    <div class="flex-1" />

    <!-- ── Sesión activa ── -->
    <template v-if="authStore.isAuthenticated">

      <!-- Aviso expiración (solo si faltan ≤120 min) -->
      <div
        v-if="authStore.minutosRestantes >= 0 && authStore.minutosRestantes <= 120"
        class="hidden sm:flex items-center gap-1.5"
      >
        <span
          class="flex items-center gap-1 px-2 py-1 rounded-lg text-[10px] font-bold leading-none"
          :class="authStore.minutosRestantes <= 30
            ? 'bg-red-500/15 text-red-300'
            : 'bg-amber-500/15 text-amber-300'"
        >
          <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ minutosLabel }}
        </span>
        <button
          @click="authStore.refresh()"
          :disabled="authStore.refreshing"
          class="hidden md:block px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest
                 bg-white/8 hover:bg-white/15 text-white/60 hover:text-white
                 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ authStore.refreshing ? '...' : 'Extender' }}
        </button>
      </div>

      <!-- Nombre y rol -->
      <div class="hidden sm:flex flex-col items-end leading-none gap-0.5">
        <span class="text-[9px] font-black uppercase tracking-widest text-white/30">
          {{ authStore.roleLabel }}
        </span>
        <span class="text-[11px] font-bold text-white/70 truncate max-w-[130px]">
          {{ authStore.userName }}
        </span>
      </div>

      <!-- Logout -->
      <button
        @click="cerrarSesion"
        :disabled="cargandoOut"
        title="Cerrar sesión"
        class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0
               text-red-400/60 hover:text-red-300 hover:bg-red-500/10
               transition-all duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg v-if="!cargandoOut" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        <svg v-else class="w-4 h-4 animate-spin" viewBox="0 0 24 24">
          <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
        </svg>
      </button>
    </template>
  </header>
</template>
