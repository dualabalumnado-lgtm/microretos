<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api.js'

// ─── Estado de acceso ─────────────────────────────────────────────────────────
const desbloqueado  = ref(sessionStorage.getItem('empresas_module_unlocked') === 'true')
const passwordInput = ref('')
const verificando   = ref(false)
const errorAcceso   = ref('')

async function verificarAcceso() {
  if (!passwordInput.value) return
  verificando.value = true
  errorAcceso.value = ''
  try {
    await api.post('/empresas/verificar-acceso', { password: passwordInput.value })
    sessionStorage.setItem('empresas_module_unlocked', 'true')
    desbloqueado.value = true
    await cargarDatos()
  } catch {
    errorAcceso.value = 'Contraseña incorrecta. Inténtalo de nuevo.'
  } finally {
    verificando.value = false
  }
}

// ─── Datos ────────────────────────────────────────────────────────────────────
const empresas   = ref([])
const familias   = ref([])
const proyectos  = ref([])
const cargando   = ref(false)
const isLoaded   = ref(false)

// ─── Filtros ──────────────────────────────────────────────────────────────────
const busqueda        = ref('')
const filtroFamilia   = ref('')
const filtroEstado    = ref('')
const filtroProvincia = ref('')
const filtroSector    = ref('')

// ─── UI ───────────────────────────────────────────────────────────────────────
const empresaExpandida  = ref(null)
const panelActivo       = ref('')
const modoNecesitas     = ref('')

// ─── Formulario email ─────────────────────────────────────────────────────────
const emailForm = ref({
  remitente : 'info@viaoptima.es',
  asunto    : '',
  mensaje   : '',
})
const enviandoEmail  = ref(false)
const emailOk        = ref(false)
const emailError     = ref('')

// ─── Formulario validación ────────────────────────────────────────────────────
const validForm = ref({
  remitente    : 'info@viaoptima.es',
  proyecto_uuid: '',
  mensaje      : '',
})
const enviandoValidacion = ref(false)
const validacionOk       = ref(false)
const validacionError    = ref('')

// ─── Cargar datos ─────────────────────────────────────────────────────────────
onMounted(async () => {
  setTimeout(() => { isLoaded.value = true }, 60)
  if (!desbloqueado.value) return
  await cargarDatos()
})

async function cargarDatos() {
  cargando.value = true
  try {
    const [empRes, famRes, proRes] = await Promise.all([
      api.get('/empresas'),
      api.get('/familias'),
      api.get('/startup/proyectos'),
    ])
    empresas.value  = empRes.data
    familias.value  = famRes.data
    proyectos.value = proRes.data.filter(p => p.estado === 'publicado')
  } finally {
    cargando.value = false
  }
}

// ─── Computed: empresas filtradas ─────────────────────────────────────────────
const empresasFiltradas = computed(() => {
  return empresas.value
    .filter(e => {
      const q = busqueda.value.toLowerCase()
      const matchNombre = !q ||
        (e.nombre_comercial || '').toLowerCase().includes(q) ||
        (e.razon_social || '').toLowerCase().includes(q)
      const matchFamilia = !filtroFamilia.value ||
        (e.familias || []).some(f => String(f.id) === filtroFamilia.value)
      const matchEstado = !filtroEstado.value || e.estado_contacto === filtroEstado.value
      const matchProv   = !filtroProvincia.value || e.provincia === filtroProvincia.value
      const matchSector = !filtroSector.value || e.sector === filtroSector.value
      return matchNombre && matchFamilia && matchEstado && matchProv && matchSector
    })
    .sort((a, b) => (a.nombre_comercial || '').localeCompare(b.nombre_comercial || '', 'es'))
})

// ─── Computed: valores únicos para filtros ────────────────────────────────────
const provincias = computed(() => {
  const set = new Set(empresas.value.map(e => e.provincia).filter(Boolean))
  return [...set].sort((a, b) => a.localeCompare(b, 'es'))
})

const sectores = computed(() => {
  const set = new Set(empresas.value.map(e => e.sector).filter(Boolean))
  return [...set].sort((a, b) => a.localeCompare(b, 'es'))
})

const estadosContacto = ['Contactado', 'Pendiente', 'Sin contactar', 'Reunión fijada', 'No interesado', 'Activo']

// ─── Interacción empresas ─────────────────────────────────────────────────────
function toggleEmpresa(empresa) {
  if (empresaExpandida.value?.id === empresa.id) {
    empresaExpandida.value = null
    panelActivo.value = ''
  } else {
    empresaExpandida.value = empresa
    panelActivo.value = ''
    emailOk.value = false
    emailError.value = ''
    validacionOk.value = false
    validacionError.value = ''
    emailForm.value = { remitente: 'info@viaoptima.es', asunto: '', mensaje: '' }
    validForm.value = { remitente: 'info@viaoptima.es', proyecto_uuid: '', mensaje: '' }
  }
}

function abrirPanel(panel) {
  panelActivo.value = panelActivo.value === panel ? '' : panel
}

// ─── Enviar email ─────────────────────────────────────────────────────────────
async function enviarEmail() {
  enviandoEmail.value = true
  emailError.value = ''
  emailOk.value = false
  try {
    await api.post(`/empresas/${empresaExpandida.value.id}/contactar`, emailForm.value)
    emailOk.value = true
    emailForm.value.asunto = ''
    emailForm.value.mensaje = ''
  } catch (e) {
    emailError.value = e.response?.data?.message || 'Error al enviar el correo.'
  } finally {
    enviandoEmail.value = false
  }
}

// ─── Enviar enlace de validación ──────────────────────────────────────────────
async function enviarValidacionEmail() {
  enviandoValidacion.value = true
  validacionError.value = ''
  validacionOk.value = false
  try {
    await api.post(`/empresas/${empresaExpandida.value.id}/enviar-validacion`, validForm.value)
    validacionOk.value = true
    validForm.value.proyecto_uuid = ''
    validForm.value.mensaje = ''
  } catch (e) {
    validacionError.value = e.response?.data?.message || 'Error al enviar el enlace.'
  } finally {
    enviandoValidacion.value = false
  }
}

// ─── helpers ──────────────────────────────────────────────────────────────────
const estadoColor = {
  'Activo':          'bg-emerald-50 text-emerald-700 border-emerald-200',
  'Contactado':      'bg-blue-50 text-blue-700 border-blue-200',
  'Reunión fijada':  'bg-amber-50 text-amber-700 border-amber-200',
  'Pendiente':       'bg-orange-50 text-orange-700 border-orange-200',
  'Sin contactar':   'bg-gray-100 text-gray-500 border-gray-200',
  'No interesado':   'bg-red-50 text-red-700 border-red-200',
}
function badgeEstado(e) {
  return estadoColor[e] || 'bg-gray-100 text-gray-500 border-gray-200'
}

function limpiarFiltros() {
  busqueda.value = ''
  filtroFamilia.value = ''
  filtroEstado.value = ''
  filtroProvincia.value = ''
  filtroSector.value = ''
}

const proyectosEmpresa = computed(() => {
  if (!empresaExpandida.value) return proyectos.value
  return proyectos.value.filter(p => p.empresa_id === empresaExpandida.value.id)
})

async function onAccesoOk() {
  sessionStorage.setItem('empresas_module_unlocked', 'true')
  desbloqueado.value = true
  await cargarDatos()
}
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] font-sans text-[#1F2937]">

    <!-- Fondo decorativo -->
    <div class="fixed top-0 right-0 w-150 h-100
                bg-[#99CC33] opacity-5 blur-[120px] rounded-full pointer-events-none z-0" />

    <!-- ════════════════════════════════════════════════════════
         GATE: Modal oscuro de contraseña
    ═══════════════════════════════════════════════════════════ -->
    <Transition name="fade">
      <div
        v-if="!desbloqueado"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        :class="isLoaded ? 'opacity-100' : 'opacity-0'"
        style="transition: opacity 0.4s ease"
      >
        <div class="relative bg-[#1a2332] border border-white/10 rounded-[2rem]
                    shadow-2xl max-w-sm w-full p-8 text-white">

          <!-- Icono candado -->
          <div class="flex justify-center mb-6">
            <div class="w-16 h-16 rounded-2xl bg-blue-500/10 border border-blue-500/20
                        flex items-center justify-center">
              <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke-width="1.5"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 11V7a5 5 0 0110 0v4"/>
              </svg>
            </div>
          </div>

          <h1 class="text-2xl font-black text-center tracking-tight mb-1">
            Módulo <span class="text-blue-400">Empresas</span>
          </h1>
          <p class="text-white/40 text-sm text-center mb-8">
            Este módulo requiere contraseña especial para proteger el contacto directo con empresas.
          </p>

          <form @submit.prevent="verificarAcceso" class="space-y-4">
            <div>
              <label class="block text-[10px] font-black uppercase tracking-widest text-white/40 mb-2">
                Contraseña de acceso
              </label>
              <input
                v-model="passwordInput"
                type="password"
                placeholder="Introduce la contraseña..."
                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3
                       text-sm text-white placeholder-white/20
                       focus:outline-none focus:border-blue-400/50 transition-colors"
              />
            </div>

            <Transition name="fade">
              <p v-if="errorAcceso" class="text-red-400 text-xs font-medium text-center">
                {{ errorAcceso }}
              </p>
            </Transition>

            <button
              type="submit"
              :disabled="verificando || !passwordInput"
              class="w-full py-3 rounded-full bg-[#00A859] text-white font-black text-xs
                     uppercase tracking-widest transition-all
                     hover:bg-[#009950] disabled:opacity-40 disabled:cursor-not-allowed"
            >
              {{ verificando ? 'Verificando...' : 'Desbloquear módulo' }}
            </button>
          </form>

          <p class="text-center mt-6 text-white/20 text-[10px]">
            Dua<span class="text-[#00A859]">Lab</span> · Módulo protegido
          </p>
        </div>
      </div>
    </Transition>

    <!-- ════════════════════════════════════════════════════════
         CONTENIDO PRINCIPAL
    ═══════════════════════════════════════════════════════════ -->
    <div
      v-if="desbloqueado"
      class="relative z-10 max-w-5xl mx-auto px-6 py-10 pl-24"
      :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
      style="transition: opacity 0.4s ease, transform 0.4s ease"
    >

      <!-- ── Cabecera ──────────────────────────────────────── -->
      <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-xl bg-[#00A859]/10 border border-[#00A859]/20
                      flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" stroke-width="1.5"/>
              <polyline points="9 22 9 12 15 12 15 22" stroke-width="1.5"/>
            </svg>
          </div>
          <div>
            <h1 class="text-2xl font-black tracking-tight text-[#121212]">
              Directorio de <span class="text-[#00A859]">Empresas</span>
            </h1>
            <p class="text-gray-400 text-xs">{{ empresas.length }} empresas en la base de datos</p>
          </div>
        </div>
      </div>

      <!-- ── ¿Qué necesitas? ────────────────────────────────── -->
      <div class="mb-8 rounded-[2rem] bg-white border border-gray-100 shadow-sm p-5">
        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-4">¿Qué necesitas?</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

          <button
            @click="modoNecesitas = modoNecesitas === 'contactar' ? '' : 'contactar'"
            :class="[
              'flex items-start gap-3 p-4 rounded-2xl border text-left transition-all',
              modoNecesitas === 'contactar'
                ? 'bg-[#00A859]/8 border-[#00A859]/25'
                : 'bg-gray-50 border-gray-100 hover:border-gray-200 hover:bg-white'
            ]"
          >
            <div class="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center"
                 :class="modoNecesitas === 'contactar' ? 'bg-[#00A859]/15' : 'bg-gray-100'">
              <svg class="w-4 h-4" :class="modoNecesitas === 'contactar' ? 'text-[#00A859]' : 'text-gray-400'"
                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
              </svg>
            </div>
            <div>
              <p class="text-xs font-bold" :class="modoNecesitas === 'contactar' ? 'text-[#00A859]' : 'text-[#1F2937]'">
                Contactar empresa
              </p>
              <p class="text-[10px] text-gray-400 mt-0.5">Enviar un email directo a la empresa</p>
            </div>
          </button>

          <button
            @click="modoNecesitas = modoNecesitas === 'validar' ? '' : 'validar'"
            :class="[
              'flex items-start gap-3 p-4 rounded-2xl border text-left transition-all',
              modoNecesitas === 'validar'
                ? 'bg-amber-50 border-amber-200'
                : 'bg-gray-50 border-gray-100 hover:border-gray-200 hover:bg-white'
            ]"
          >
            <div class="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center"
                 :class="modoNecesitas === 'validar' ? 'bg-amber-100' : 'bg-gray-100'">
              <svg class="w-4 h-4" :class="modoNecesitas === 'validar' ? 'text-amber-600' : 'text-gray-400'"
                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="text-xs font-bold" :class="modoNecesitas === 'validar' ? 'text-amber-700' : 'text-[#1F2937]'">
                Enviar validación
              </p>
              <p class="text-[10px] text-gray-400 mt-0.5">Compartir enlace de microproyecto</p>
            </div>
          </button>

          <button
            @click="modoNecesitas = modoNecesitas === 'ficha' ? '' : 'ficha'"
            :class="[
              'flex items-start gap-3 p-4 rounded-2xl border text-left transition-all',
              modoNecesitas === 'ficha'
                ? 'bg-blue-50 border-blue-200'
                : 'bg-gray-50 border-gray-100 hover:border-gray-200 hover:bg-white'
            ]"
          >
            <div class="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center"
                 :class="modoNecesitas === 'ficha' ? 'bg-blue-100' : 'bg-gray-100'">
              <svg class="w-4 h-4" :class="modoNecesitas === 'ficha' ? 'text-blue-600' : 'text-gray-400'"
                   fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
            </div>
            <div>
              <p class="text-xs font-bold" :class="modoNecesitas === 'ficha' ? 'text-blue-700' : 'text-[#1F2937]'">
                Ver ficha completa
              </p>
              <p class="text-[10px] text-gray-400 mt-0.5">Consultar todos los datos de la empresa</p>
            </div>
          </button>

        </div>

        <Transition name="fade">
          <p v-if="modoNecesitas" class="mt-4 text-xs text-gray-400 text-center">
            <template v-if="modoNecesitas === 'contactar'">
              Busca la empresa en la lista y despliégala — verás el formulario de contacto por email.
            </template>
            <template v-else-if="modoNecesitas === 'validar'">
              Busca la empresa y despliégala — selecciona un microproyecto publicado y envía el enlace de validación.
            </template>
            <template v-else-if="modoNecesitas === 'ficha'">
              Busca la empresa y despliégala para ver todos sus datos de contacto y actividad.
            </template>
          </p>
        </Transition>
      </div>

      <!-- ── Filtros ─────────────────────────────────────────── -->
      <div class="mb-5 rounded-[2rem] bg-white border border-gray-100 shadow-sm p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">

          <!-- Búsqueda -->
          <div class="relative lg:col-span-2">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300 pointer-events-none"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <circle cx="11" cy="11" r="8" stroke-width="2"/>
              <path d="M21 21l-4.35-4.35" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <input
              v-model="busqueda"
              type="text"
              placeholder="Buscar empresa por nombre..."
              class="w-full pl-9 pr-4 py-2.5 bg-white border border-gray-200 rounded-2xl
                     text-sm text-[#1F2937] placeholder-gray-400 shadow-sm
                     focus:outline-none focus:border-[#00A859] transition-colors"
            />
          </div>

          <select
            v-model="filtroFamilia"
            class="px-3 py-2.5 bg-white border border-gray-200 rounded-2xl shadow-sm
                   text-sm text-[#1F2937] focus:outline-none focus:border-[#00A859] transition-colors"
          >
            <option value="">Todas las familias</option>
            <option v-for="f in familias" :key="f.id" :value="String(f.id)">{{ f.nombre }}</option>
          </select>

          <select
            v-model="filtroEstado"
            class="px-3 py-2.5 bg-white border border-gray-200 rounded-2xl shadow-sm
                   text-sm text-[#1F2937] focus:outline-none focus:border-[#00A859] transition-colors"
          >
            <option value="">Todos los estados</option>
            <option v-for="e in estadosContacto" :key="e" :value="e">{{ e }}</option>
          </select>

          <select
            v-model="filtroProvincia"
            class="px-3 py-2.5 bg-white border border-gray-200 rounded-2xl shadow-sm
                   text-sm text-[#1F2937] focus:outline-none focus:border-[#00A859] transition-colors"
          >
            <option value="">Todas las provincias</option>
            <option v-for="p in provincias" :key="p" :value="p">{{ p }}</option>
          </select>

          <select
            v-model="filtroSector"
            class="px-3 py-2.5 bg-white border border-gray-200 rounded-2xl shadow-sm
                   text-sm text-[#1F2937] focus:outline-none focus:border-[#00A859] transition-colors"
          >
            <option value="">Todos los sectores</option>
            <option v-for="s in sectores" :key="s" :value="s">{{ s }}</option>
          </select>

        </div>

        <div class="flex items-center justify-between mt-3">
          <p class="text-[10px] text-gray-400">
            {{ empresasFiltradas.length }} empresa{{ empresasFiltradas.length !== 1 ? 's' : '' }} mostradas
          </p>
          <button
            v-if="busqueda || filtroFamilia || filtroEstado || filtroProvincia || filtroSector"
            @click="limpiarFiltros"
            class="text-[10px] font-bold text-gray-400 hover:text-[#00A859] transition-colors uppercase tracking-widest"
          >
            Limpiar filtros
          </button>
        </div>
      </div>

      <!-- ── Cargando ────────────────────────────────────────── -->
      <div v-if="cargando" class="flex flex-col items-center justify-center py-24">
        <svg class="animate-spin w-10 h-10 text-[#00A859] mb-3" viewBox="0 0 24 24">
          <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
        </svg>
        <p class="text-gray-400 text-xs uppercase tracking-widest">Cargando empresas...</p>
      </div>

      <!-- ── Lista de empresas ───────────────────────────────── -->
      <div v-else-if="empresasFiltradas.length" class="space-y-2">
        <div
          v-for="empresa in empresasFiltradas"
          :key="empresa.id"
          class="rounded-[2rem] border overflow-hidden transition-all duration-200 bg-white"
          :class="empresaExpandida?.id === empresa.id
            ? 'border-[#00A859]/20 shadow-md'
            : 'border-gray-100 shadow-sm hover:border-gray-200 hover:shadow'"
        >

          <!-- Cabecera empresa (clickable) -->
          <button
            @click="toggleEmpresa(empresa)"
            class="w-full flex items-center gap-4 px-5 py-4 text-left"
          >
            <!-- Inicial -->
            <div class="w-9 h-9 rounded-xl bg-[#00A859]/10 border border-[#00A859]/15
                        flex items-center justify-center shrink-0 font-black text-sm text-[#00A859] select-none">
              {{ (empresa.nombre_comercial || '?')[0].toUpperCase() }}
            </div>

            <!-- Info principal -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-bold text-sm text-[#121212] truncate">{{ empresa.nombre_comercial }}</span>
                <span v-if="empresa.estado_contacto"
                      :class="['text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full border', badgeEstado(empresa.estado_contacto)]">
                  {{ empresa.estado_contacto }}
                </span>
              </div>
              <div class="flex items-center gap-3 mt-0.5 flex-wrap">
                <span v-if="empresa.sector" class="text-[10px] text-gray-400">{{ empresa.sector }}</span>
                <span v-if="empresa.municipio" class="text-[10px] text-gray-400">
                  {{ empresa.municipio }}<span v-if="empresa.provincia">, {{ empresa.provincia }}</span>
                </span>
                <span v-if="empresa.email_general || empresa.email_contacto"
                      class="text-[10px] text-[#00A859]">
                  {{ empresa.email_contacto || empresa.email_general }}
                </span>
              </div>
            </div>

            <!-- Chevron -->
            <svg
              class="w-4 h-4 text-gray-300 transition-transform duration-200 shrink-0"
              :class="empresaExpandida?.id === empresa.id ? 'rotate-180' : ''"
              fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <!-- Panel expandido -->
          <Transition name="slide-down">
            <div v-if="empresaExpandida?.id === empresa.id" class="border-t border-gray-100">

              <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">

                <!-- ─ Ficha de datos ─ -->
                <div class="space-y-4">
                  <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Datos de la empresa</p>

                  <div class="space-y-2">
                    <template v-if="empresa.razon_social">
                      <div class="flex justify-between gap-3">
                        <span class="text-[10px] text-gray-400">Razón social</span>
                        <span class="text-[10px] text-gray-700 text-right">{{ empresa.razon_social }}</span>
                      </div>
                    </template>
                    <template v-if="empresa.cif">
                      <div class="flex justify-between gap-3">
                        <span class="text-[10px] text-gray-400">CIF</span>
                        <span class="text-[10px] text-gray-700">{{ empresa.cif }}</span>
                      </div>
                    </template>
                    <template v-if="empresa.telefono">
                      <div class="flex justify-between gap-3">
                        <span class="text-[10px] text-gray-400">Teléfono</span>
                        <a :href="`tel:${empresa.telefono}`" class="text-[10px] text-[#00A859] hover:underline">
                          {{ empresa.telefono }}
                        </a>
                      </div>
                    </template>
                    <template v-if="empresa.email_general">
                      <div class="flex justify-between gap-3">
                        <span class="text-[10px] text-gray-400">Email general</span>
                        <a :href="`mailto:${empresa.email_general}`" class="text-[10px] text-[#00A859] hover:underline truncate">
                          {{ empresa.email_general }}
                        </a>
                      </div>
                    </template>
                    <template v-if="empresa.web">
                      <div class="flex justify-between gap-3">
                        <span class="text-[10px] text-gray-400">Web</span>
                        <a :href="empresa.web.startsWith('http') ? empresa.web : 'https://'+empresa.web"
                           target="_blank" rel="noopener"
                           class="text-[10px] text-[#00A859] hover:underline truncate">
                          {{ empresa.web }}
                        </a>
                      </div>
                    </template>
                    <template v-if="empresa.persona_contacto">
                      <div class="flex justify-between gap-3">
                        <span class="text-[10px] text-gray-400">Persona de contacto</span>
                        <span class="text-[10px] text-gray-700">
                          {{ empresa.persona_contacto }}
                          <span v-if="empresa.posicion_contacto" class="text-gray-400"> · {{ empresa.posicion_contacto }}</span>
                        </span>
                      </div>
                    </template>
                    <template v-if="empresa.email_contacto">
                      <div class="flex justify-between gap-3">
                        <span class="text-[10px] text-gray-400">Email contacto</span>
                        <a :href="`mailto:${empresa.email_contacto}`" class="text-[10px] text-[#00A859] hover:underline truncate">
                          {{ empresa.email_contacto }}
                        </a>
                      </div>
                    </template>
                    <template v-if="empresa.actividad">
                      <div class="flex justify-between gap-3">
                        <span class="text-[10px] text-gray-400">Actividad</span>
                        <span class="text-[10px] text-gray-700 text-right max-w-[60%]">{{ empresa.actividad }}</span>
                      </div>
                    </template>
                    <template v-if="empresa.tamano">
                      <div class="flex justify-between gap-3">
                        <span class="text-[10px] text-gray-400">Tamaño</span>
                        <span class="text-[10px] text-gray-700">{{ empresa.tamano }}</span>
                      </div>
                    </template>
                    <template v-if="empresa.horario_atencion">
                      <div class="flex justify-between gap-3">
                        <span class="text-[10px] text-gray-400">Horario</span>
                        <span class="text-[10px] text-gray-700 text-right">{{ empresa.horario_atencion }}</span>
                      </div>
                    </template>
                  </div>

                  <!-- Dirección -->
                  <div v-if="empresa.direccion || empresa.municipio"
                       class="pt-3 border-t border-gray-100">
                    <p class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Dirección</p>
                    <p class="text-[10px] text-gray-500 leading-relaxed">
                      <span v-if="empresa.direccion">
                        {{ empresa.direccion }}
                        <span v-if="empresa.numero"> {{ empresa.numero }}</span>
                        <span v-if="empresa.otros_direccion">, {{ empresa.otros_direccion }}</span>
                      </span>
                      <br v-if="empresa.direccion && (empresa.municipio || empresa.codigo_postal)"/>
                      <span v-if="empresa.codigo_postal">{{ empresa.codigo_postal }} </span>
                      <span v-if="empresa.municipio">{{ empresa.municipio }}</span>
                      <span v-if="empresa.provincia">, {{ empresa.provincia }}</span>
                    </p>
                  </div>

                  <!-- Familias profesionales -->
                  <div v-if="empresa.familias?.length" class="pt-3 border-t border-gray-100">
                    <p class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Familias profesionales</p>
                    <div class="flex flex-wrap gap-1.5">
                      <span v-for="f in empresa.familias" :key="f.id"
                            class="text-[9px] px-2 py-0.5 rounded-full bg-[#00A859]/10
                                   border border-[#00A859]/20 text-[#00A859] font-medium">
                        {{ f.nombre }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- ─ Acciones ─ -->
                <div class="space-y-3">
                  <p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Acciones</p>

                  <!-- Botones de acción -->
                  <div class="grid grid-cols-2 gap-2">
                    <button
                      @click="abrirPanel('email')"
                      :class="[
                        'flex items-center justify-center gap-2 py-2.5 px-3 rounded-2xl border text-xs font-bold transition-all',
                        panelActivo === 'email'
                          ? 'bg-[#00A859]/8 border-[#00A859]/25 text-[#00A859]'
                          : 'bg-gray-50 border-gray-200 text-gray-600 hover:border-gray-300 hover:text-[#1F2937]'
                      ]"
                    >
                      <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                      Enviar email
                    </button>

                    <button
                      @click="abrirPanel('validacion')"
                      :class="[
                        'flex items-center justify-center gap-2 py-2.5 px-3 rounded-2xl border text-xs font-bold transition-all',
                        panelActivo === 'validacion'
                          ? 'bg-amber-50 border-amber-200 text-amber-700'
                          : 'bg-gray-50 border-gray-200 text-gray-600 hover:border-gray-300 hover:text-[#1F2937]'
                      ]"
                    >
                      <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                      </svg>
                      Enviar validación
                    </button>
                  </div>

                  <!-- ─ Formulario email ─ -->
                  <Transition name="slide-down">
                    <div v-if="panelActivo === 'email'"
                         class="rounded-2xl bg-[#00A859]/5 border border-[#00A859]/15 p-4 space-y-3">
                      <p class="text-[9px] font-black uppercase tracking-widest text-[#00A859]">Enviar correo</p>

                      <div v-if="emailOk"
                           class="flex items-center gap-2 p-3 rounded-xl bg-[#00A859]/10 border border-[#00A859]/20">
                        <svg class="w-4 h-4 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <p class="text-xs font-bold text-[#00A859]">Correo enviado correctamente.</p>
                      </div>

                      <template v-if="!emailOk">
                        <div>
                          <label class="block text-[9px] text-gray-500 uppercase tracking-wider mb-1">Remitente (tu email)</label>
                          <input v-model="emailForm.remitente" type="email"
                                 class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2
                                        text-xs text-[#1F2937] placeholder-gray-400 shadow-sm
                                        focus:outline-none focus:border-[#00A859] transition-colors"/>
                        </div>
                        <div>
                          <label class="block text-[9px] text-gray-500 uppercase tracking-wider mb-1">Destinatario</label>
                          <p class="text-xs text-gray-600 px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl shadow-sm">
                            {{ empresa.email_contacto || empresa.email_general || 'Sin email registrado' }}
                          </p>
                        </div>
                        <div>
                          <label class="block text-[9px] text-gray-500 uppercase tracking-wider mb-1">Asunto</label>
                          <input v-model="emailForm.asunto" type="text" placeholder="Asunto del correo..."
                                 class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2
                                        text-xs text-[#1F2937] placeholder-gray-400 shadow-sm
                                        focus:outline-none focus:border-[#00A859] transition-colors"/>
                        </div>
                        <div>
                          <label class="block text-[9px] text-gray-500 uppercase tracking-wider mb-1">Mensaje</label>
                          <textarea v-model="emailForm.mensaje" rows="4"
                                    placeholder="Escribe tu mensaje aquí..."
                                    class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2
                                           text-xs text-[#1F2937] placeholder-gray-400 shadow-sm resize-none
                                           focus:outline-none focus:border-[#00A859] transition-colors"/>
                        </div>
                        <p v-if="emailError" class="text-xs text-red-500 font-medium">{{ emailError }}</p>
                        <button
                          @click="enviarEmail"
                          :disabled="enviandoEmail || !emailForm.asunto || !emailForm.mensaje || !(empresa.email_contacto || empresa.email_general)"
                          class="w-full py-2.5 rounded-full bg-[#00A859] text-white text-xs font-black
                                 uppercase tracking-widest transition-all shadow-sm
                                 hover:bg-[#009950] disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                          {{ enviandoEmail ? 'Enviando...' : 'Enviar correo' }}
                        </button>
                        <p v-if="!(empresa.email_contacto || empresa.email_general)"
                           class="text-[10px] text-amber-600 text-center">
                          Esta empresa no tiene email registrado.
                        </p>
                      </template>
                    </div>
                  </Transition>

                  <!-- ─ Formulario validación ─ -->
                  <Transition name="slide-down">
                    <div v-if="panelActivo === 'validacion'"
                         class="rounded-2xl bg-amber-50 border border-amber-200 p-4 space-y-3">
                      <p class="text-[9px] font-black uppercase tracking-widest text-amber-700">Enviar enlace de validación</p>

                      <div v-if="validacionOk"
                           class="flex items-center gap-2 p-3 rounded-xl bg-amber-100 border border-amber-200">
                        <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                        <p class="text-xs font-bold text-amber-800">Enlace enviado correctamente.</p>
                      </div>

                      <template v-if="!validacionOk">
                        <div>
                          <label class="block text-[9px] text-gray-500 uppercase tracking-wider mb-1">Remitente (tu email)</label>
                          <input v-model="validForm.remitente" type="email"
                                 class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2
                                        text-xs text-[#1F2937] placeholder-gray-400 shadow-sm
                                        focus:outline-none focus:border-amber-400 transition-colors"/>
                        </div>

                        <div>
                          <label class="block text-[9px] text-gray-500 uppercase tracking-wider mb-1">Microproyecto a validar</label>
                          <select v-model="validForm.proyecto_uuid"
                                  class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2
                                         text-xs text-[#1F2937] shadow-sm
                                         focus:outline-none focus:border-amber-400 transition-colors">
                            <option value="">Selecciona un microproyecto publicado...</option>
                            <option v-for="p in proyectos" :key="p.uuid" :value="p.uuid">
                              {{ p.titulo }}<template v-if="p.empresa_nombre"> · {{ p.empresa_nombre }}</template>
                            </option>
                          </select>
                          <p v-if="proyectos.length === 0" class="text-[10px] text-gray-400 mt-1">
                            No hay microproyectos publicados. Publícalos en el StartUp Day.
                          </p>
                        </div>

                        <div>
                          <label class="block text-[9px] text-gray-500 uppercase tracking-wider mb-1">
                            Mensaje adicional <span class="normal-case font-normal">(opcional)</span>
                          </label>
                          <textarea v-model="validForm.mensaje" rows="3"
                                    placeholder="Añade contexto o instrucciones adicionales para la empresa..."
                                    class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2
                                           text-xs text-[#1F2937] placeholder-gray-400 shadow-sm resize-none
                                           focus:outline-none focus:border-amber-400 transition-colors"/>
                        </div>

                        <p v-if="validacionError" class="text-xs text-red-500 font-medium">{{ validacionError }}</p>

                        <button
                          @click="enviarValidacionEmail"
                          :disabled="enviandoValidacion || !validForm.proyecto_uuid || !(empresa.email_contacto || empresa.email_general)"
                          class="w-full py-2.5 rounded-full bg-amber-500 text-white text-xs font-black
                                 uppercase tracking-widest transition-all shadow-sm
                                 hover:bg-amber-400 disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                          {{ enviandoValidacion ? 'Enviando...' : 'Enviar enlace de validación' }}
                        </button>

                        <p v-if="!(empresa.email_contacto || empresa.email_general)"
                           class="text-[10px] text-amber-600 text-center">
                          Esta empresa no tiene email registrado.
                        </p>
                      </template>
                    </div>
                  </Transition>

                </div>
              </div>

            </div>
          </Transition>

        </div>
      </div>

      <!-- Sin resultados -->
      <div v-else-if="!cargando"
           class="text-center py-24 rounded-[2rem] border border-dashed border-gray-200 bg-white shadow-sm">
        <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
          <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22" stroke-width="1.5"/>
          </svg>
        </div>
        <p class="text-gray-400 text-sm">No se han encontrado empresas con estos filtros.</p>
        <button @click="limpiarFiltros"
                class="mt-3 text-xs font-bold text-[#00A859] hover:text-[#009950] transition-colors">
          Limpiar filtros
        </button>
      </div>

    </div>
  </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

.slide-down-enter-active { transition: all 0.22s ease; overflow: hidden; }
.slide-down-leave-active { transition: all 0.18s ease; overflow: hidden; }
.slide-down-enter-from, .slide-down-leave-to {
  opacity: 0;
  max-height: 0;
  transform: translateY(-6px);
}
.slide-down-enter-to, .slide-down-leave-from {
  max-height: 1200px;
}

select option { background: white; color: #1F2937; }
</style>
