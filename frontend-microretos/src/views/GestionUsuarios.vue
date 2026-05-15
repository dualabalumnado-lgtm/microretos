<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import api from '../api.js'

// ── Estado principal ────────────────────────────────────────────────
const usuarios    = ref([])
const papelera    = ref([])
const cargando    = ref(false)
const vista       = ref('activos')
const filtroRol   = ref(0)

// ── Modal: crear cuenta ─────────────────────────────────────────────
const modalCrear  = ref(false)
const creando     = ref(false)
const form        = ref({ name: '', email: '', password: '', role: '2' })
const formErrors  = ref({})
const msgCrear    = ref('')

// ── Modal: éxito tras crear ─────────────────────────────────────────
const modalExito       = ref(false)
const cuentaRecienCreada = ref(null)   // el objeto usuario recién creado

// ── Puntero animado al botón Activar ───────────────────────────────
const highlightId      = ref(null)     // id del usuario a destacar
const activarBtns      = ref({})       // map id → el DOM del botón
const tooltipStyle     = ref({})
let   highlightTimer   = null

// ── Modal: editar cuenta ────────────────────────────────────────────
const modalEditar        = ref(false)
const editando           = ref(false)
const usuarioEditando    = ref(null)
const formEditar         = ref({ name: '', email: '', password: '', role: '2' })
const formEditarErrors   = ref({})
const msgEditar          = ref('')

function abrirModalEditar(usuario) {
  usuarioEditando.value  = usuario
  formEditar.value       = { name: usuario.name, email: usuario.email, password: '', role: String(usuario.role) }
  formEditarErrors.value = {}
  msgEditar.value        = ''
  modalEditar.value      = true
}

async function guardarEdicion() {
  formEditarErrors.value = {}
  msgEditar.value        = ''
  editando.value         = true
  try {
    const payload = {
      name:  formEditar.value.name,
      email: formEditar.value.email,
      role:  formEditar.value.role,
    }
    if (formEditar.value.password) payload.password = formEditar.value.password
    const { data } = await api.patch(`/admin/usuarios/${usuarioEditando.value.id}`, payload)
    reemplazar(usuarios.value, data.data)
    modalEditar.value = false
    mostrarToast('Cuenta actualizada correctamente.')
  } catch (e) {
    if (e.response?.status === 422) {
      const errs = e.response.data.errors ?? {}
      formEditarErrors.value = Object.fromEntries(
        Object.entries(errs).map(([k, v]) => [k, v[0]])
      )
    } else {
      msgEditar.value = e.response?.data?.message ?? 'Error al actualizar la cuenta.'
    }
  } finally {
    editando.value = false
  }
}

// ── Modal: asociar centro ───────────────────────────────────────────
const modalCentro       = ref(false)
const usuarioCentro     = ref(null)    // usuario al que se asocia centro
const centros           = ref([])
const cargandoCentros   = ref(false)
const busquedaCentro    = ref('')
const asociandoCentro   = ref(false)

// ── Confirmación + Toast ────────────────────────────────────────────
const confirm = ref({ show: false, title: '', body: '', action: null, danger: true })
const toast   = ref({ show: false, msg: '', ok: true })

// ── Computed ────────────────────────────────────────────────────────
const usuariosFiltrados = computed(() => {
  const lista = vista.value === 'papelera' ? papelera.value : usuarios.value
  if (filtroRol.value === 0) return lista
  return lista.filter(u => u.role === filtroRol.value)
})

const totalActivos  = computed(() => usuarios.value.length)
const totalDocentes = computed(() => usuarios.value.filter(u => u.role === 2).length)
const totalEmpresas = computed(() => usuarios.value.filter(u => u.role === 3).length)
const totalPapelera = computed(() => papelera.value.length)

function fuzzyNorm(s) {
  return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '')
}

const centrosFiltrados = computed(() => {
  if (!busquedaCentro.value.trim()) return centros.value
  const words = fuzzyNorm(busquedaCentro.value).split(/\s+/).filter(Boolean)
  return centros.value.filter(c =>
    words.every(w => fuzzyNorm(c.nombre).includes(w))
  )
})

// ── API: usuarios ───────────────────────────────────────────────────
async function cargar() {
  cargando.value = true
  try {
    if (vista.value === 'papelera') {
      const { data } = await api.get('/admin/usuarios/papelera')
      papelera.value = data.data
    } else {
      const { data } = await api.get('/admin/usuarios')
      usuarios.value = data.data
    }
  } finally {
    cargando.value = false
  }
}

async function crearUsuario() {
  formErrors.value = {}
  msgCrear.value   = ''
  creando.value    = true
  try {
    const { data } = await api.post('/admin/usuarios', form.value)
    usuarios.value.push(data.data)
    modalCrear.value      = false
    cuentaRecienCreada.value = data.data
    form.value = { name: '', email: '', password: '', role: '2' }
    // Pequeño delay para que el DOM renderice la nueva fila antes del modal
    await nextTick()
    modalExito.value = true
  } catch (e) {
    if (e.response?.status === 422) {
      const errs = e.response.data.errors ?? {}
      formErrors.value = Object.fromEntries(
        Object.entries(errs).map(([k, v]) => [k, v[0]])
      )
    } else {
      msgCrear.value = e.response?.data?.message ?? 'Error al crear la cuenta.'
    }
  } finally {
    creando.value = false
  }
}

function cerrarModalExito() {
  modalExito.value = false
  // Lanzar el puntero al botón Activar de la nueva cuenta
  if (cuentaRecienCreada.value && !cuentaRecienCreada.value.is_active) {
    activarHighlight(cuentaRecienCreada.value.id)
  }
}

// ── Puntero animado ─────────────────────────────────────────────────
function activarHighlight(id) {
  if (highlightTimer) clearTimeout(highlightTimer)
  highlightId.value = id

  // Calcular posición del tooltip sobre el botón
  nextTick(() => {
    const btn = activarBtns.value[id]
    if (btn) {
      const rect = btn.getBoundingClientRect()
      tooltipStyle.value = {
        top:  `${rect.top + window.scrollY - 52}px`,
        left: `${rect.left + rect.width / 2}px`,
      }
      btn.scrollIntoView({ behavior: 'smooth', block: 'center' })
    }
  })

  highlightTimer = setTimeout(() => {
    highlightId.value = null
  }, 7000)
}

function onActivarClick(usuario) {
  if (highlightId.value === usuario.id) {
    highlightId.value = null
    if (highlightTimer) clearTimeout(highlightTimer)
  }
  activar(usuario)
}

// ── API: acciones sobre usuarios ────────────────────────────────────
async function activar(usuario) {
  try {
    const { data } = await api.patch(`/admin/usuarios/${usuario.id}/activar`)
    reemplazar(usuarios.value, data.data)
    mostrarToast('Cuenta activada correctamente.')
  } catch (e) {
    mostrarToast(e.response?.data?.message ?? 'Error al activar.', false)
  }
}

async function toggleBloquear(usuario) {
  const accion = usuario.is_blocked ? 'desbloquear' : 'bloquear'
  pedirConfirmacion(
    `${accion.charAt(0).toUpperCase() + accion.slice(1)} cuenta`,
    `¿Seguro que quieres ${accion} la cuenta de ${usuario.name}?`,
    async () => {
      const { data } = await api.patch(`/admin/usuarios/${usuario.id}/bloquear`)
      reemplazar(usuarios.value, data.data)
      mostrarToast(`Cuenta ${data.data.is_blocked ? 'bloqueada' : 'desbloqueada'}.`)
    },
    !usuario.is_blocked
  )
}

async function enviarPapelera(usuario) {
  pedirConfirmacion(
    'Eliminar cuenta',
    `¿Enviar a la papelera la cuenta de ${usuario.name}? Podrás restaurarla después.`,
    async () => {
      await api.delete(`/admin/usuarios/${usuario.id}`)
      usuarios.value = usuarios.value.filter(u => u.id !== usuario.id)
      mostrarToast('Cuenta enviada a la papelera.')
    },
    true
  )
}

async function restaurar(usuario) {
  try {
    const { data } = await api.post(`/admin/usuarios/${usuario.id}/restaurar`)
    papelera.value  = papelera.value.filter(u => u.id !== usuario.id)
    usuarios.value.push(data.data)
    mostrarToast('Cuenta restaurada.')
  } catch (e) {
    mostrarToast(e.response?.data?.message ?? 'Error al restaurar.', false)
  }
}

async function destruir(usuario) {
  pedirConfirmacion(
    'Eliminación definitiva',
    `Esta acción es irreversible. ¿Eliminar permanentemente la cuenta de ${usuario.name}?`,
    async () => {
      await api.delete(`/admin/usuarios/${usuario.id}/destruir`)
      papelera.value = papelera.value.filter(u => u.id !== usuario.id)
      mostrarToast('Cuenta eliminada definitivamente.')
    },
    true
  )
}

// ── Centros: búsqueda y asociación ──────────────────────────────────
async function abrirModalCentro(usuario) {
  usuarioCentro.value  = usuario
  busquedaCentro.value = ''
  modalCentro.value    = true

  if (centros.value.length === 0) {
    cargandoCentros.value = true
    try {
      const { data } = await api.get('/centros')
      centros.value = data
    } finally {
      cargandoCentros.value = false
    }
  }
}

async function seleccionarCentro(centro) {
  if (!usuarioCentro.value) return
  asociandoCentro.value = true
  try {
    const { data } = await api.patch(
      `/admin/usuarios/${usuarioCentro.value.id}/centro`,
      { centro_educativo_id: centro ? centro.id : null }
    )
    reemplazar(usuarios.value, data.data)
    usuarioCentro.value = data.data
    mostrarToast(`Centro ${centro ? `"${centro.nombre}" asociado` : 'desvinculado'} correctamente.`)
    if (!centro) {
      modalCentro.value = false
    }
  } catch (e) {
    mostrarToast(e.response?.data?.message ?? 'Error al asociar el centro.', false)
  } finally {
    asociandoCentro.value = false
  }
}

async function desvincularCentro() {
  pedirConfirmacion(
    'Desvincular centro',
    `¿Quitar el centro educativo de ${usuarioCentro.value?.name}?`,
    () => seleccionarCentro(null),
    false
  )
}

// ── Helpers ─────────────────────────────────────────────────────────
function reemplazar(lista, item) {
  const idx = lista.findIndex(u => u.id === item.id)
  if (idx !== -1) lista[idx] = item
}

function pedirConfirmacion(title, body, action, danger = true) {
  confirm.value = { show: true, title, body, action, danger }
}

async function ejecutarConfirm() {
  try {
    await confirm.value.action()
  } catch (e) {
    mostrarToast(e.response?.data?.message ?? 'Error.', false)
  } finally {
    confirm.value.show = false
  }
}

function mostrarToast(msg, ok = true) {
  toast.value = { show: true, msg, ok }
  setTimeout(() => { toast.value.show = false }, 3500)
}

function cambiarVista(v) {
  vista.value     = v
  filtroRol.value = 0
  cargar()
}

// ── Formato ─────────────────────────────────────────────────────────
const ROLE_COLORS = {
  2: { bg: 'bg-blue-500/15 text-blue-300 border-blue-500/30',    label: 'Docente' },
  3: { bg: 'bg-amber-500/15 text-amber-300 border-amber-500/30', label: 'Empresa' },
}
function roleChip(role) {
  return ROLE_COLORS[role] ?? { bg: 'bg-white/10 text-white/50 border-white/10', label: 'Admin' }
}
function fmtDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })
}

onMounted(async () => {
  cargando.value = true
  try {
    const [resActivos, resPapelera] = await Promise.all([
      api.get('/admin/usuarios'),
      api.get('/admin/usuarios/papelera'),
    ])
    usuarios.value = resActivos.data.data
    papelera.value = resPapelera.data.data
  } finally {
    cargando.value = false
  }
})
</script>

<template>
  <div class="min-h-screen bg-[#111827] text-white px-4 py-8 lg:px-8">

    <!-- ── Cabecera ────────────────────────────────────────── -->
    <div class="max-w-5xl mx-auto mb-8 flex flex-col sm:flex-row sm:items-end gap-4">
      <div class="flex-1">
        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-[#00A859]/70 mb-1">Administración</p>
        <h1 class="text-2xl font-black tracking-tight">Gestión de cuentas</h1>
        <p class="text-sm text-white/40 mt-1">Crea, activa, bloquea y elimina cuentas de docentes y empresas.</p>
      </div>
      <button
        @click="modalCrear = true"
        class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#00A859] hover:bg-[#009950]
               text-white font-black text-xs uppercase tracking-widest transition-all shrink-0"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 5v14M5 12h14"/>
        </svg>
        Nueva cuenta
      </button>
    </div>

    <!-- ── Contadores ───────────────────────────────────────── -->
    <div v-if="!cargando" class="max-w-5xl mx-auto mb-5 flex flex-wrap gap-2">
      <!-- Total activos -->
      <div class="flex items-center gap-2 px-3 py-1.5 bg-white/5 rounded-2xl border border-white/10">
        <svg class="w-4 h-4 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 7a4 4 0 100 8 4 4 0 000-8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
        </svg>
        <span class="font-black text-xl text-white">{{ totalActivos }}</span>
        <span class="text-xs font-semibold text-white/40 uppercase tracking-wider">cuentas</span>
      </div>
      <!-- Docentes -->
      <div class="flex items-center gap-2 px-3 py-1.5 bg-blue-500/10 rounded-2xl border border-blue-500/20">
        <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 3h6v4H9V3zM9 12l2 2 4-4"/>
        </svg>
        <span class="font-black text-xl text-blue-300">{{ totalDocentes }}</span>
        <span class="text-xs font-semibold text-blue-400/60 uppercase tracking-wider">docentes</span>
      </div>
      <!-- Empresas -->
      <div class="flex items-center gap-2 px-3 py-1.5 bg-amber-500/10 rounded-2xl border border-amber-500/20">
        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        <span class="font-black text-xl text-amber-300">{{ totalEmpresas }}</span>
        <span class="text-xs font-semibold text-amber-400/60 uppercase tracking-wider">empresas</span>
      </div>
      <!-- Papelera -->
      <div v-if="totalPapelera > 0" class="flex items-center gap-2 px-3 py-1.5 bg-red-500/10 rounded-2xl border border-red-500/20">
        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <polyline points="3 6 5 6 21 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6M10 11v6M14 11v6M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
        </svg>
        <span class="font-black text-xl text-red-400">{{ totalPapelera }}</span>
        <span class="text-xs font-semibold text-red-400/60 uppercase tracking-wider">reciclados</span>
      </div>
    </div>

    <!-- ── Tabs + filtros ─────────────────────────────────── -->
    <div class="max-w-5xl mx-auto mb-5 flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
      <div class="flex gap-1 p-1 bg-white/5 border border-white/10 rounded-xl">
        <button @click="cambiarVista('activos')"
          :class="vista === 'activos' ? 'bg-[#00A859]/20 text-[#00A859] border border-[#00A859]/40' : 'text-white/40 hover:text-white/70'"
          class="px-4 py-1.5 rounded-lg text-xs font-black uppercase tracking-wider transition-all">
          Activos
        </button>
        <button @click="cambiarVista('papelera')"
          :class="vista === 'papelera' ? 'bg-red-500/20 text-red-300 border border-red-500/40' : 'text-white/40 hover:text-white/70'"
          class="px-4 py-1.5 rounded-lg text-xs font-black uppercase tracking-wider transition-all flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
          </svg>
          Papelera
        </button>
      </div>
      <div class="flex gap-1 p-1 bg-white/5 border border-white/10 rounded-xl">
        <button v-for="f in [{ val: 0, label: 'Todos' }, { val: 2, label: 'Docentes' }, { val: 3, label: 'Empresas' }]"
          :key="f.val" @click="filtroRol = f.val"
          :class="filtroRol === f.val ? 'bg-white/10 text-white' : 'text-white/40 hover:text-white/70'"
          class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all">
          {{ f.label }}
        </button>
      </div>
    </div>

    <!-- ── Lista de usuarios ──────────────────────────────── -->
    <div class="max-w-5xl mx-auto">
      <div v-if="cargando" class="flex items-center justify-center py-20 text-white/30">
        <svg class="w-6 h-6 animate-spin mr-3" viewBox="0 0 24 24">
          <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
        </svg>
        Cargando...
      </div>

      <div v-else-if="usuariosFiltrados.length === 0" class="text-center py-20 text-white/30 text-sm">
        {{ vista === 'papelera' ? 'La papelera está vacía.' : 'No hay cuentas en este filtro.' }}
      </div>

      <div v-else class="space-y-2">
        <div
          v-for="u in usuariosFiltrados" :key="u.id"
          class="flex flex-col gap-3 bg-[#1F2937] border rounded-2xl px-4 py-3.5 transition-all"
          :class="u.is_blocked ? 'border-red-500/20 bg-red-500/5' : vista === 'papelera' ? 'border-white/8 opacity-70' : 'border-white/8 hover:border-white/15'"
        >
          <!-- Fila principal -->
          <div class="flex flex-col sm:flex-row sm:items-center gap-3">

            <!-- Avatar + info -->
            <div class="flex items-center gap-3 flex-1 min-w-0">
              <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 font-black text-sm"
                   :class="u.role === 2 ? 'bg-blue-500/20 text-blue-300' : 'bg-amber-500/20 text-amber-300'">
                {{ u.name.charAt(0).toUpperCase() }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-bold text-sm text-white truncate">{{ u.name }}</span>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border"
                        :class="roleChip(u.role).bg">
                    {{ roleChip(u.role).label }}
                  </span>
                  <span v-if="!u.is_active"
                        class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider
                               bg-yellow-500/15 text-yellow-300 border border-yellow-500/30">
                    Sin activar
                  </span>
                  <span v-if="u.is_blocked"
                        class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider
                               bg-red-500/15 text-red-300 border border-red-500/30">
                    Bloqueada
                  </span>
                </div>
                <p class="text-xs text-white/40 truncate mt-0.5">{{ u.email }}</p>
                <!-- Centro asociado (solo docentes) -->
                <p v-if="u.role === 2 && u.centro_nombre"
                   class="text-[10px] text-blue-300/70 mt-0.5 flex items-center gap-1">
                  <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                  </svg>
                  {{ u.centro_nombre }}
                </p>
              </div>
            </div>

            <!-- Fecha -->
            <div class="hidden md:block text-right shrink-0">
              <p class="text-[10px] text-white/25 uppercase tracking-wider">Creada</p>
              <p class="text-xs text-white/40">{{ fmtDate(u.created_at) }}</p>
            </div>
            <div v-if="vista === 'papelera'" class="hidden md:block text-right shrink-0">
              <p class="text-[10px] text-white/25 uppercase tracking-wider">Eliminada</p>
              <p class="text-xs text-red-300/60">{{ fmtDate(u.deleted_at) }}</p>
            </div>

            <!-- Acciones activos -->
            <div v-if="vista === 'activos'" class="flex items-center gap-1.5 shrink-0 flex-wrap">

              <!-- Activar con highlight y ref dinámico -->
              <div v-if="!u.is_active" class="relative">
                <!-- Tooltip puntero -->
                <Transition name="pointer">
                  <div v-if="highlightId === u.id"
                       class="absolute -top-11 left-1/2 -translate-x-1/2 z-10 pointer-events-none
                              whitespace-nowrap px-3 py-1.5 rounded-lg
                              bg-[#00A859] text-white text-[10px] font-black shadow-lg
                              flex items-center gap-1.5">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    Activa esta cuenta aquí
                    <!-- Flecha apuntando abajo -->
                    <span class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3
                                 bg-[#00A859] rotate-45 rounded-sm"/>
                  </div>
                </Transition>

                <button
                  :ref="el => { if (el) activarBtns[u.id] = el }"
                  @click="onActivarClick(u)"
                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black
                         uppercase tracking-wider transition-all
                         bg-[#00A859]/15 text-[#00A859] border border-[#00A859]/30
                         hover:bg-[#00A859]/25"
                  :class="highlightId === u.id ? 'ring-2 ring-[#00A859]/60 ring-offset-1 ring-offset-[#1F2937] animate-pulse-soft' : ''"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                  </svg>
                  Activar
                </button>
              </div>

              <!-- Editar -->
              <button @click="abrirModalEditar(u)"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black
                       uppercase tracking-wider bg-white/5 text-white/50 border border-white/10
                       hover:bg-white/10 hover:text-white/80 transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Editar
              </button>

              <!-- Bloquear / Desbloquear -->
              <button @click="toggleBloquear(u)"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black
                       uppercase tracking-wider border transition-all"
                :class="u.is_blocked
                  ? 'bg-yellow-500/15 text-yellow-300 border-yellow-500/30 hover:bg-yellow-500/25'
                  : 'bg-white/5 text-white/50 border-white/10 hover:bg-white/10 hover:text-white/70'">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke-width="2"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
                {{ u.is_blocked ? 'Desbloquear' : 'Bloquear' }}
              </button>

              <!-- Eliminar -->
              <button @click="enviarPapelera(u)"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black
                       uppercase tracking-wider bg-red-500/10 text-red-400 border border-red-500/20
                       hover:bg-red-500/20 transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                  <polyline points="3 6 5 6 21 6"/>
                  <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                </svg>
                Eliminar
              </button>
            </div>

            <!-- Acciones papelera -->
            <div v-else class="flex items-center gap-1.5 shrink-0">
              <button @click="restaurar(u)"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black
                       uppercase tracking-wider bg-[#00A859]/15 text-[#00A859]
                       border border-[#00A859]/30 hover:bg-[#00A859]/25 transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Restaurar
              </button>
              <button @click="destruir(u)"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[10px] font-black
                       uppercase tracking-wider bg-red-500/15 text-red-300
                       border border-red-500/30 hover:bg-red-500/25 transition-all">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Definitivo
              </button>
            </div>
          </div>

          <!-- Fila secundaria: asociar centro (solo docentes activos) -->
          <div v-if="vista === 'activos' && u.role === 2" class="border-t border-white/5 pt-2.5">
            <button @click="abrirModalCentro(u)"
              class="flex items-center gap-2 text-[10px] font-black uppercase tracking-wider
                     text-white/30 hover:text-blue-300 transition-colors">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
              </svg>
              {{ u.centro_nombre ? `Centro: ${u.centro_nombre} — cambiar` : 'Asociar centro educativo' }}
            </button>
          </div>

        </div>
      </div>
    </div>

    <!-- ══ MODAL: Crear cuenta ═════════════════════════════════════ -->
    <Transition name="overlay">
      <div v-if="modalCrear"
           class="fixed inset-0 z-[9000] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
           @click.self="modalCrear = false">
        <Transition name="modal-scale">
          <div v-if="modalCrear"
               class="relative bg-[#1a2332] border border-white/10 rounded-[1.75rem] shadow-2xl w-full max-w-md p-8">
            <button @click="modalCrear = false"
              class="absolute top-4 right-4 w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10
                     flex items-center justify-center text-white/40 hover:text-white transition-all">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>

            <h2 class="text-lg font-black mb-1">Nueva cuenta</h2>
            <p class="text-xs text-white/40 mb-6">
              La cuenta quedará pendiente de activación hasta que la valides.
            </p>

            <form @submit.prevent="crearUsuario" class="space-y-4">
              <!-- Nombre -->
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-1.5">Nombre</label>
                <input v-model="form.name" type="text" placeholder="Nombre completo"
                  class="w-full bg-white/5 border rounded-xl px-4 py-2.5 text-sm text-white placeholder-white/20
                         outline-none transition-all focus:border-[#00A859]/50 focus:ring-2 focus:ring-[#00A859]/10"
                  :class="formErrors.name ? 'border-red-500/50' : 'border-white/10'" />
                <p v-if="formErrors.name" class="text-[10px] text-red-400 mt-1">{{ formErrors.name }}</p>
              </div>

              <!-- Email -->
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-1.5">Correo electrónico</label>
                <input v-model="form.email" type="email" placeholder="correo@ejemplo.com"
                  class="w-full bg-white/5 border rounded-xl px-4 py-2.5 text-sm text-white placeholder-white/20
                         outline-none transition-all focus:border-[#00A859]/50 focus:ring-2 focus:ring-[#00A859]/10"
                  :class="formErrors.email ? 'border-red-500/50' : 'border-white/10'" />
                <p v-if="formErrors.email" class="text-[10px] text-red-400 mt-1">{{ formErrors.email }}</p>
              </div>

              <!-- Contraseña -->
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-1.5">Contraseña temporal</label>
                <input v-model="form.password" type="password" placeholder="Mín. 8 caracteres, mayúsculas y números"
                  class="w-full bg-white/5 border rounded-xl px-4 py-2.5 text-sm text-white placeholder-white/20
                         outline-none transition-all focus:border-[#00A859]/50 focus:ring-2 focus:ring-[#00A859]/10"
                  :class="formErrors.password ? 'border-red-500/50' : 'border-white/10'" />
                <p v-if="formErrors.password" class="text-[10px] text-red-400 mt-1">{{ formErrors.password }}</p>
              </div>

              <!-- Rol -->
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2">Rol</label>
                <div class="flex gap-3">
                  <button type="button" @click="form.role = '2'"
                    :class="form.role === '2' ? 'bg-blue-500/20 border-blue-500/50 text-blue-300' : 'bg-white/5 border-white/10 text-white/50 hover:text-white/80'"
                    class="flex-1 flex flex-col items-center gap-1.5 py-3 rounded-xl border text-xs font-bold transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 3h6v4H9V3z"/>
                    </svg>
                    Docente
                  </button>
                  <button type="button" @click="form.role = '3'"
                    :class="form.role === '3' ? 'bg-amber-500/20 border-amber-500/50 text-amber-300' : 'bg-white/5 border-white/10 text-white/50 hover:text-white/80'"
                    class="flex-1 flex flex-col items-center gap-1.5 py-3 rounded-xl border text-xs font-bold transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Empresa
                  </button>
                </div>
                <p v-if="formErrors.role" class="text-[10px] text-red-400 mt-1">{{ formErrors.role }}</p>
              </div>

              <p v-if="msgCrear" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-3 py-2">
                {{ msgCrear }}
              </p>

              <button type="submit" :disabled="creando"
                class="w-full py-3 rounded-xl bg-[#00A859] hover:bg-[#009950] text-white font-black
                       text-xs uppercase tracking-widest transition-all disabled:opacity-50 disabled:cursor-not-allowed mt-2">
                {{ creando ? 'Creando...' : 'Crear cuenta' }}
              </button>
            </form>
          </div>
        </Transition>
      </div>
    </Transition>

    <!-- ══ MODAL: Éxito al crear cuenta ═══════════════════════════ -->
    <Transition name="overlay">
      <div v-if="modalExito"
           class="fixed inset-0 z-[9100] flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
        <Transition name="modal-scale">
          <div v-if="modalExito"
               class="bg-[#1a2332] border border-white/10 rounded-[1.75rem] shadow-2xl w-full max-w-sm p-8 text-center">

            <!-- Icono -->
            <div class="mx-auto mb-5 w-16 h-16 rounded-2xl bg-[#00A859]/15 border border-[#00A859]/30
                        flex items-center justify-center">
              <svg class="w-8 h-8 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
              </svg>
            </div>

            <h2 class="text-xl font-black mb-2">¡Cuenta creada!</h2>
            <p class="text-sm text-white/50 leading-relaxed mb-2">
              La cuenta de
              <span class="text-white font-bold">{{ cuentaRecienCreada?.name }}</span>
              se ha creado correctamente.
            </p>

            <!-- Aviso destacado -->
            <div class="mt-4 mb-6 rounded-xl bg-yellow-500/10 border border-yellow-500/25 px-4 py-3.5
                        flex items-start gap-3 text-left">
              <svg class="w-4 h-4 text-yellow-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
              <div>
                <p class="text-xs font-black text-yellow-300 uppercase tracking-wider mb-0.5">Pendiente de activación</p>
                <p class="text-xs text-white/50">
                  La cuenta aún no puede iniciar sesión.
                  Pulsa el botón <span class="text-[#00A859] font-bold">Activar</span> en la fila
                  de esta cuenta para habilitarla.
                </p>
              </div>
            </div>

            <button @click="cerrarModalExito"
              class="w-full py-3 rounded-xl bg-[#00A859] hover:bg-[#009950] text-white
                     font-black text-xs uppercase tracking-widest transition-all">
              Entendido, ir a activar
            </button>
          </div>
        </Transition>
      </div>
    </Transition>

    <!-- ══ MODAL: Asociar centro ═══════════════════════════════════ -->
    <Transition name="overlay">
      <div v-if="modalCentro"
           class="fixed inset-0 z-[9000] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
           @click.self="modalCentro = false">
        <Transition name="modal-scale">
          <div v-if="modalCentro"
               class="relative bg-[#1a2332] border border-white/10 rounded-[1.75rem] shadow-2xl
                      w-full max-w-lg flex flex-col" style="max-height: 85vh">

            <!-- Cabecera -->
            <div class="px-6 pt-6 pb-4 border-b border-white/8 shrink-0">
              <button @click="modalCentro = false"
                class="absolute top-4 right-4 w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10
                       flex items-center justify-center text-white/40 hover:text-white transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
              <h2 class="text-base font-black mb-0.5">Asociar centro educativo</h2>
              <p class="text-xs text-white/40">
                Docente: <span class="text-white font-bold">{{ usuarioCentro?.name }}</span>
              </p>

              <!-- Centro actual -->
              <div v-if="usuarioCentro?.centro_nombre"
                   class="mt-3 flex items-center justify-between
                          bg-blue-500/10 border border-blue-500/20 rounded-xl px-3 py-2">
                <div class="flex items-center gap-2 text-xs text-blue-300">
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                  </svg>
                  <span class="font-bold">{{ usuarioCentro.centro_nombre }}</span>
                </div>
                <button @click="desvincularCentro"
                  class="text-[10px] font-black uppercase tracking-wider text-red-400/70 hover:text-red-400 transition-colors">
                  Quitar
                </button>
              </div>

              <!-- Buscador -->
              <div class="relative mt-3">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <circle cx="11" cy="11" r="8" stroke-width="2"/>
                  <path stroke-linecap="round" stroke-width="2" d="M21 21l-4.35-4.35"/>
                </svg>
                <input
                  v-model="busquedaCentro"
                  type="text"
                  placeholder="Buscar por nombre del centro…"
                  autofocus
                  class="w-full bg-white/5 border border-white/10 rounded-xl pl-9 pr-4 py-2.5
                         text-sm text-white placeholder-white/25 outline-none
                         focus:border-[#00A859]/50 focus:ring-2 focus:ring-[#00A859]/10 transition-all"
                />
              </div>
            </div>

            <!-- Lista de centros -->
            <div class="flex-1 overflow-y-auto px-4 py-3 space-y-1">
              <div v-if="cargandoCentros" class="flex items-center justify-center py-10 text-white/30">
                <svg class="w-5 h-5 animate-spin mr-2" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
                </svg>
                Cargando centros…
              </div>

              <div v-else-if="centrosFiltrados.length === 0"
                   class="text-center py-10 text-white/30 text-sm">
                No se encontraron centros con ese nombre.
              </div>

              <button
                v-else
                v-for="c in centrosFiltrados" :key="c.id"
                @click="seleccionarCentro(c)"
                :disabled="asociandoCentro"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left
                       border transition-all disabled:opacity-50"
                :class="usuarioCentro?.centro_educativo_id === c.id
                  ? 'bg-blue-500/20 border-blue-500/40 text-blue-200'
                  : 'bg-white/3 border-white/8 text-white/70 hover:bg-white/8 hover:border-white/20 hover:text-white'"
              >
                <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                     :class="usuarioCentro?.centro_educativo_id === c.id ? 'bg-blue-500/30' : 'bg-white/8'">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                       :class="usuarioCentro?.centro_educativo_id === c.id ? 'text-blue-300' : 'text-white/40'">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-bold truncate">{{ c.nombre }}</p>
                </div>
                <svg v-if="usuarioCentro?.centro_educativo_id === c.id"
                     class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
              </button>
            </div>

            <div class="px-6 py-4 border-t border-white/8 shrink-0">
              <p class="text-[10px] text-white/25 text-center">
                {{ centrosFiltrados.length }} centro{{ centrosFiltrados.length !== 1 ? 's' : '' }} encontrado{{ centrosFiltrados.length !== 1 ? 's' : '' }}
              </p>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>

    <!-- ══ MODAL: Editar cuenta ══════════════════════════════════ -->
    <Transition name="overlay">
      <div v-if="modalEditar"
           class="fixed inset-0 z-[9000] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
           @click.self="modalEditar = false">
        <Transition name="modal-scale">
          <div v-if="modalEditar"
               class="relative bg-[#1a2332] border border-white/10 rounded-[1.75rem] shadow-2xl w-full max-w-md p-8">

            <!-- Aviso de seguridad -->
            <div class="mb-5 rounded-xl bg-amber-500/10 border border-amber-500/25 px-4 py-3 flex items-start gap-3">
              <svg class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>
              <div>
                <p class="text-xs font-black text-amber-300 uppercase tracking-wider mb-0.5">Edición de cuenta sensible</p>
                <p class="text-xs text-white/50">Estás modificando los datos de <span class="text-white font-bold">{{ usuarioEditando?.name }}</span>. Cualquier cambio tendrá efecto inmediato.</p>
              </div>
            </div>

            <button @click="modalEditar = false"
              class="absolute top-4 right-4 w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10
                     flex items-center justify-center text-white/40 hover:text-white transition-all">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>

            <h2 class="text-lg font-black mb-1">Editar cuenta</h2>
            <p class="text-xs text-white/40 mb-6">Modifica los datos de la cuenta. Deja la contraseña en blanco para no cambiarla.</p>

            <form @submit.prevent="guardarEdicion" class="space-y-4">
              <!-- Nombre -->
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-1.5">Nombre</label>
                <input v-model="formEditar.name" type="text" placeholder="Nombre completo"
                  class="w-full bg-white/5 border rounded-xl px-4 py-2.5 text-sm text-white placeholder-white/20
                         outline-none transition-all focus:border-[#00A859]/50 focus:ring-2 focus:ring-[#00A859]/10"
                  :class="formEditarErrors.name ? 'border-red-500/50' : 'border-white/10'" />
                <p v-if="formEditarErrors.name" class="text-[10px] text-red-400 mt-1">{{ formEditarErrors.name }}</p>
              </div>

              <!-- Email -->
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-1.5">Correo electrónico</label>
                <input v-model="formEditar.email" type="email" placeholder="correo@ejemplo.com"
                  class="w-full bg-white/5 border rounded-xl px-4 py-2.5 text-sm text-white placeholder-white/20
                         outline-none transition-all focus:border-[#00A859]/50 focus:ring-2 focus:ring-[#00A859]/10"
                  :class="formEditarErrors.email ? 'border-red-500/50' : 'border-white/10'" />
                <p v-if="formEditarErrors.email" class="text-[10px] text-red-400 mt-1">{{ formEditarErrors.email }}</p>
              </div>

              <!-- Nueva contraseña (opcional) -->
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-1.5">Nueva contraseña <span class="normal-case text-white/25 font-normal">(opcional)</span></label>
                <input v-model="formEditar.password" type="password" placeholder="Dejar en blanco para no cambiar"
                  class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-white/20
                         outline-none transition-all focus:border-[#00A859]/50 focus:ring-2 focus:ring-[#00A859]/10"
                  :class="formEditarErrors.password ? 'border-red-500/50' : 'border-white/10'" />
                <p v-if="formEditarErrors.password" class="text-[10px] text-red-400 mt-1">{{ formEditarErrors.password }}</p>
              </div>

              <!-- Rol -->
              <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2">Rol</label>
                <div class="flex gap-3">
                  <button type="button" @click="formEditar.role = '2'"
                    :class="formEditar.role === '2' ? 'bg-blue-500/20 border-blue-500/50 text-blue-300' : 'bg-white/5 border-white/10 text-white/50 hover:text-white/80'"
                    class="flex-1 flex flex-col items-center gap-1.5 py-3 rounded-xl border text-xs font-bold transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 3h6v4H9V3z"/>
                    </svg>
                    Docente
                  </button>
                  <button type="button" @click="formEditar.role = '3'"
                    :class="formEditar.role === '3' ? 'bg-amber-500/20 border-amber-500/50 text-amber-300' : 'bg-white/5 border-white/10 text-white/50 hover:text-white/80'"
                    class="flex-1 flex flex-col items-center gap-1.5 py-3 rounded-xl border text-xs font-bold transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Empresa
                  </button>
                </div>
              </div>

              <p v-if="msgEditar" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-3 py-2">
                {{ msgEditar }}
              </p>

              <button type="submit" :disabled="editando"
                class="w-full py-3 rounded-xl bg-[#00A859] hover:bg-[#009950] text-white font-black
                       text-xs uppercase tracking-widest transition-all disabled:opacity-50 disabled:cursor-not-allowed mt-2">
                {{ editando ? 'Guardando...' : 'Guardar cambios' }}
              </button>
            </form>
          </div>
        </Transition>
      </div>
    </Transition>

    <!-- ══ MODAL: Confirmación ════════════════════════════════════ -->
    <Transition name="overlay">
      <div v-if="confirm.show"
           class="fixed inset-0 z-[9200] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
           @click.self="confirm.show = false">
        <div class="bg-[#1a2332] border border-white/10 rounded-2xl shadow-2xl w-full max-w-sm p-6">
          <h3 class="font-black text-base mb-2">{{ confirm.title }}</h3>
          <p class="text-sm text-white/50 mb-6">{{ confirm.body }}</p>
          <div class="flex gap-3">
            <button @click="confirm.show = false"
              class="flex-1 py-2.5 rounded-xl bg-white/5 border border-white/10 text-xs font-black
                     uppercase tracking-widest text-white/50 hover:bg-white/10 transition-all">
              Cancelar
            </button>
            <button @click="ejecutarConfirm"
              :class="confirm.danger
                ? 'bg-red-500/20 border-red-500/30 text-red-300 hover:bg-red-500/30'
                : 'bg-[#00A859]/20 border-[#00A859]/30 text-[#00A859] hover:bg-[#00A859]/30'"
              class="flex-1 py-2.5 rounded-xl border text-xs font-black uppercase tracking-widest transition-all">
              Confirmar
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ══ TOAST ══════════════════════════════════════════════════ -->
    <Transition name="toast">
      <div v-if="toast.show"
           class="fixed bottom-6 right-6 z-[9300] flex items-center gap-3 px-4 py-3
                  rounded-xl border shadow-xl text-xs font-bold"
           :class="toast.ok
             ? 'bg-[#00A859]/20 border-[#00A859]/40 text-[#00A859]'
             : 'bg-red-500/20 border-red-500/40 text-red-300'">
        <svg v-if="toast.ok" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
        <svg v-else class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ toast.msg }}
      </div>
    </Transition>

  </div>
</template>

<style scoped>
/* Transiciones generales */
.overlay-enter-active, .overlay-leave-active { transition: opacity 0.25s ease; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }

.modal-scale-enter-active { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-scale-leave-active { transition: all 0.2s ease; }
.modal-scale-enter-from   { opacity: 0; transform: scale(0.92) translateY(10px); }
.modal-scale-leave-to     { opacity: 0; transform: scale(0.96); }

.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to       { opacity: 0; transform: translateY(10px); }

/* Tooltip puntero al botón Activar */
.pointer-enter-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.pointer-leave-active { transition: all 0.2s ease; }
.pointer-enter-from   { opacity: 0; transform: translateX(-50%) translateY(6px); }
.pointer-leave-to     { opacity: 0; transform: translateX(-50%) translateY(-4px); }

/* Pulso suave para el botón destacado */
@keyframes pulse-soft {
  0%, 100% { box-shadow: 0 0 0 3px rgba(0, 168, 89, 0.5), 0 0 0 6px rgba(0, 168, 89, 0.15); }
  50%       { box-shadow: 0 0 0 5px rgba(0, 168, 89, 0.35), 0 0 0 10px rgba(0, 168, 89, 0.05); }
}
.animate-pulse-soft { animation: pulse-soft 1.4s ease-in-out infinite; }
</style>
