<script setup>
/**
 * CentroEducativoModal.vue
 * Modal para crear O editar un centro educativo.
 *
 * Modo crear : prop `centro` = null  → POST /centros
 * Modo editar: prop `centro` = { id, nombre, ciclos[] } → PUT /centros/{id}
 *
 * Emite:
 *   @centro-creado    { id, nombre }  — solo en modo crear
 *   @centro-guardado  { id, nombre }  — siempre (crear y editar)
 *   @cerrar
 */
import { ref, reactive, computed, watch } from 'vue'
import api from '../api.js'

const props = defineProps({
  visible: { type: Boolean, default: false },
  centro:  { type: Object,  default: null  },   // null = crear, objeto = editar
})

const emit = defineEmits(['centro-creado', 'centro-guardado', 'cerrar'])

// ─── Estado ────────────────────────────────────────────────
const nombre       = ref('')
const nombreError  = ref('')
const errorGlobal  = ref('')
const guardando    = ref(false)

const familias           = ref([])
const familiasExpandidas = ref(new Set())
const ciclosPorFamilia   = reactive({})
const cargandoCiclos     = reactive({})
const seleccionados      = ref([])

const modoEditar = computed(() => !!props.centro)

// ─── Abrir modal ────────────────────────────────────────────
watch(() => props.visible, async (v) => {
  if (!v) return
  resetear()

  if (!familias.value.length) {
    const { data } = await api.get('/familias')
    familias.value = data
  }

  if (props.centro) {
    // Modo edición: pre-cargar datos existentes
    nombre.value      = props.centro.nombre
    seleccionados.value = (props.centro.ciclos ?? []).map(c => c.id)

    // Pre-expandir y pre-cargar familias que ya tienen ciclos seleccionados
    const familiaIdsConCiclos = [
      ...new Set((props.centro.ciclos ?? []).map(c => c.familia_id).filter(Boolean))
    ]
    for (const famId of familiaIdsConCiclos) {
      const familia = familias.value.find(f => f.id === famId)
      if (familia && !ciclosPorFamilia[famId]) {
        familiasExpandidas.value.add(famId)
        cargandoCiclos[famId] = true
        try {
          const { data } = await api.get(`/familias/${encodeURIComponent(familia.nombre)}/ciclos`)
          ciclosPorFamilia[famId] = data
        } catch (e) {
          ciclosPorFamilia[famId] = []
        } finally {
          cargandoCiclos[famId] = false
        }
      }
    }
  }
})

// ─── Accordion por familia ──────────────────────────────────
async function toggleFamilia(familia) {
  if (familiasExpandidas.value.has(familia.id)) {
    familiasExpandidas.value.delete(familia.id)
    return
  }
  familiasExpandidas.value.add(familia.id)
  if (!ciclosPorFamilia[familia.id]) {
    cargandoCiclos[familia.id] = true
    try {
      const { data } = await api.get(`/familias/${encodeURIComponent(familia.nombre)}/ciclos`)
      ciclosPorFamilia[familia.id] = data
    } catch (e) {
      console.error(e)
      ciclosPorFamilia[familia.id] = []
    } finally {
      cargandoCiclos[familia.id] = false
    }
  }
}

// ─── Helpers de selección ───────────────────────────────────
function ciclosSeleccionadosDeFamilia(familiaId) {
  const ciclos = ciclosPorFamilia[familiaId]
  if (!ciclos) return 0
  return ciclos.filter(c => seleccionados.value.includes(c.id)).length
}

function toggleTodosDeFamilia(familiaId) {
  const ciclos = ciclosPorFamilia[familiaId] || []
  const ids    = ciclos.map(c => c.id)
  const todosSeleccionados = ids.every(id => seleccionados.value.includes(id))
  if (todosSeleccionados) {
    seleccionados.value = seleccionados.value.filter(id => !ids.includes(id))
  } else {
    const nuevos = ids.filter(id => !seleccionados.value.includes(id))
    seleccionados.value = [...seleccionados.value, ...nuevos]
  }
}

function todosDeFamiliaSeleccionados(familiaId) {
  const ciclos = ciclosPorFamilia[familiaId]
  if (!ciclos?.length) return false
  return ciclos.every(c => seleccionados.value.includes(c.id))
}

// Badge para familias sin ciclos cargados pero con preselecciones (modo editar)
function ciclosPreseleccionadosDeFamilia(familiaId) {
  if (ciclosPorFamilia[familiaId]) return ciclosSeleccionadosDeFamilia(familiaId)
  // Sin ciclos cargados: contar desde los ciclos del centro
  return (props.centro?.ciclos ?? []).filter(c => c.familia_id === familiaId).length
}

const totalSeleccionados = computed(() => seleccionados.value.length)

// ─── Guardar ────────────────────────────────────────────────
async function guardar() {
  nombreError.value = ''
  errorGlobal.value = ''

  if (!nombre.value.trim()) {
    nombreError.value = 'El nombre del centro es obligatorio'
    return
  }
  if (seleccionados.value.length === 0) {
    errorGlobal.value = 'Selecciona al menos un ciclo formativo'
    return
  }

  guardando.value = true
  try {
    if (modoEditar.value) {
      const { data } = await api.put(`/centros/${props.centro.id}`, {
        nombre:    nombre.value.trim(),
        ciclosIds: seleccionados.value,
      })
      emit('centro-guardado', data.centro)
    } else {
      const { data } = await api.post('/centros', {
        nombre:    nombre.value.trim(),
        ciclosIds: seleccionados.value,
      })
      emit('centro-creado',   data.centro)
      emit('centro-guardado', data.centro)
    }
  } catch (e) {
    if (e.response?.status === 422) {
      const errors = e.response.data.errors
      if (errors?.nombre) nombreError.value = errors.nombre[0]
      else errorGlobal.value = e.response.data.message || 'Error al guardar'
    } else {
      errorGlobal.value = 'Error al guardar el centro. Inténtalo de nuevo.'
    }
  } finally {
    guardando.value = false
  }
}

function resetear() {
  nombre.value        = ''
  nombreError.value   = ''
  errorGlobal.value   = ''
  seleccionados.value = []
  familiasExpandidas.value.clear()
}
</script>

<template>
  <Teleport to="body">
    <Transition name="cce-overlay">
      <div
        v-if="visible"
        class="cce-overlay"
        @click.self="$emit('cerrar')"
      >
        <Transition name="cce-card">
          <div v-if="visible" class="cce-card">

            <!-- Cabecera -->
            <div class="cce-header">
              <div class="cce-icon-box" :class="modoEditar ? 'cce-icon-box--edit' : ''">
                <svg v-if="modoEditar" class="w-7 h-7 text-[#99CC33]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <svg v-else class="w-7 h-7 text-[#99CC33]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <h2 class="cce-title">
                  {{ modoEditar ? `Editar centro` : 'Nuevo centro educativo' }}
                </h2>
                <p v-if="modoEditar" class="cce-sub cce-sub--edit">
                  {{ centro.nombre }}
                </p>
                <p v-else class="cce-sub">
                  Define el nombre y los ciclos formativos que imparte
                </p>
              </div>
            </div>

            <!-- Nombre -->
            <div class="mt-7">
              <label class="cce-label">Nombre del centro *</label>
              <input
                v-model="nombre"
                class="cce-input"
                :class="{ 'cce-input-err': nombreError }"
                placeholder="Ej: IES Nombre del Centro"
                @keydown.enter.prevent="guardar"
              />
              <p v-if="nombreError" class="cce-err">{{ nombreError }}</p>
            </div>

            <!-- Familias y ciclos -->
            <div class="mt-6">
              <div class="flex items-center justify-between mb-3">
                <p class="cce-label mb-0">Ciclos que imparte *</p>
                <span v-if="totalSeleccionados > 0"
                  class="text-[10px] font-black uppercase tracking-widest
                         bg-[#00A859]/10 text-[#00A859] px-2.5 py-1 rounded-full">
                  {{ totalSeleccionados }} seleccionados
                </span>
              </div>
              <p class="text-xs text-gray-400 mb-3">
                Despliega cada familia para ver y seleccionar sus ciclos.
                <span v-if="modoEditar" class="text-amber-500 font-semibold">
                  Los cambios reemplazarán la selección actual.
                </span>
              </p>

              <div class="space-y-2 max-h-[42vh] overflow-y-auto pr-1 cce-scroll">
                <div
                  v-for="familia in familias"
                  :key="familia.id"
                  class="rounded-2xl border overflow-hidden transition-colors"
                  :class="ciclosPreseleccionadosDeFamilia(familia.id) > 0
                    ? 'border-[#00A859]/30 bg-[#F0FBF4]'
                    : 'border-gray-100 bg-white'"
                >
                  <!-- Cabecera familia -->
                  <button
                    type="button"
                    @click="toggleFamilia(familia)"
                    class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-black/5 transition-colors"
                  >
                    <div class="w-2 h-2 rounded-full shrink-0"
                      :class="ciclosPreseleccionadosDeFamilia(familia.id) > 0 ? 'bg-[#00A859]' : 'bg-gray-300'"/>
                    <span class="flex-1 font-bold text-sm text-[#1F2937]">{{ familia.nombre }}</span>
                    <span v-if="ciclosPreseleccionadosDeFamilia(familia.id) > 0"
                      class="text-[10px] font-black uppercase tracking-widest
                             bg-[#00A859]/15 text-[#00A859] px-2 py-0.5 rounded-full shrink-0">
                      {{ ciclosPreseleccionadosDeFamilia(familia.id) }}
                    </span>
                    <svg
                      class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0"
                      :class="familiasExpandidas.has(familia.id) ? 'rotate-180' : ''"
                      fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                  </button>

                  <!-- Lista de ciclos -->
                  <div v-if="familiasExpandidas.has(familia.id)" class="border-t border-gray-100 px-4 py-3">
                    <div v-if="cargandoCiclos[familia.id]" class="flex items-center gap-2 py-2">
                      <div class="w-4 h-4 rounded-full border-2 border-[#00A859]/30 border-t-[#00A859] animate-spin shrink-0"/>
                      <span class="text-xs text-gray-400">Cargando ciclos...</span>
                    </div>
                    <div v-else-if="ciclosPorFamilia[familia.id]?.length">
                      <!-- Seleccionar todos -->
                      <label class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-gray-500
                                   cursor-pointer pb-2 mb-2 border-b border-gray-100 hover:text-[#00A859]">
                        <input
                          type="checkbox"
                          :checked="todosDeFamiliaSeleccionados(familia.id)"
                          @change="toggleTodosDeFamilia(familia.id)"
                          class="accent-[#00A859] w-3.5 h-3.5 shrink-0"
                        />
                        Seleccionar todos
                      </label>
                      <div class="flex flex-col gap-1.5">
                        <label
                          v-for="ciclo in ciclosPorFamilia[familia.id]"
                          :key="ciclo.id"
                          class="flex items-center gap-2 text-sm text-[#1F2937] cursor-pointer hover:text-[#00A859] py-0.5"
                        >
                          <input
                            type="checkbox"
                            :value="ciclo.id"
                            v-model="seleccionados"
                            class="accent-[#00A859] w-4 h-4 shrink-0"
                          />
                          {{ ciclo.nombre }}
                        </label>
                      </div>
                    </div>
                    <p v-else class="text-xs text-gray-400 italic py-1">No hay ciclos disponibles para esta familia.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Error global -->
            <Transition name="cce-fade">
              <div v-if="errorGlobal" class="cce-alert-error mt-4">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ errorGlobal }}</span>
              </div>
            </Transition>

            <!-- Acciones -->
            <div class="cce-actions">
              <button type="button" @click="$emit('cerrar')" class="cce-btn-ghost flex-1">Cancelar</button>
              <button type="button" @click="guardar" :disabled="guardando" class="cce-btn-green flex-[2]">
                <svg v-if="guardando" class="animate-spin w-4 h-4" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
                </svg>
                <svg v-else-if="modoEditar" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ guardando ? 'Guardando...' : (modoEditar ? 'Guardar cambios' : 'Crear centro') }}
              </button>
            </div>

            <!-- X -->
            <button type="button" class="cce-x" @click="$emit('cerrar')">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>

          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.cce-overlay {
  position: fixed; inset: 0;
  background: rgba(10, 18, 25, 0.82);
  backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
  display: flex; align-items: center; justify-content: center;
  z-index: 10000; padding: 1rem;
}
.cce-card {
  position: relative; background: #fff;
  border: 1px solid #e5e7eb; border-radius: 2rem; padding: 2.5rem;
  width: 100%; max-width: 640px; max-height: 90vh; overflow-y: auto;
  box-shadow: 0 0 0 1px rgba(0,0,0,.03), 0 24px 48px rgba(0,0,0,.18), 0 0 80px rgba(153,204,51,.07);
  scrollbar-width: thin; scrollbar-color: #BBE8D0 transparent;
}
.cce-card::-webkit-scrollbar       { width: 5px; }
.cce-card::-webkit-scrollbar-thumb { background: #BBE8D0; border-radius: 3px; }

.cce-scroll { scrollbar-width: thin; scrollbar-color: #d1d5db transparent; }
.cce-scroll::-webkit-scrollbar       { width: 4px; }
.cce-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }

.cce-header   { display: flex; align-items: flex-start; gap: 1rem; }
.cce-icon-box { flex-shrink: 0; width: 52px; height: 52px; background: rgba(31,41,55,0.9); border-radius: 1rem; display: flex; align-items: center; justify-content: center; }
.cce-icon-box--edit { background: rgba(180,83,9,0.12); }
.cce-title    { font-size: 1.3rem; font-weight: 900; color: #1F2937; letter-spacing: -.025em; line-height: 1.2; }
.cce-sub      { font-size: .78rem; color: #6b7280; margin-top: .3rem; font-weight: 500; }
.cce-sub--edit { color: #d97706; font-weight: 700; }

.cce-label { display: block; font-size: .625rem; font-weight: 900; letter-spacing: .2em; text-transform: uppercase; color: #6b7280; margin-bottom: .45rem; margin-left: .25rem; }
.cce-err   { font-size: .7rem; color: #ef4444; font-weight: 700; margin-top: .35rem; margin-left: .25rem; }

.cce-input {
  width: 100%; border: 2px solid #BBE8D0; border-radius: 1rem; padding: .85rem 1rem;
  font-size: .875rem; font-weight: 600; color: #1F2937; background: #F0FBF4; outline: none; transition: all .2s;
}
.cce-input::placeholder { color: #9CA3AF; }
.cce-input:focus        { background: #E6F7EE; border-color: #00A859; box-shadow: 0 0 0 4px rgba(0,168,89,.12); }
.cce-input-err          { border-color: #fca5a5 !important; background: #fff5f5 !important; }

.cce-alert-error { display: flex; align-items: center; gap: .75rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 1rem; padding: .75rem 1rem; color: #dc2626; font-size: .78rem; font-weight: 700; }

.cce-actions { display: flex; gap: .75rem; margin-top: 2rem; }

.cce-btn-green { display: flex; align-items: center; justify-content: center; gap: .5rem; padding: 1rem 1.5rem; background: linear-gradient(135deg, #1F2937, #374151); color: #fff; border: none; border-radius: 1rem; font-weight: 900; font-size: .7rem; letter-spacing: .15em; text-transform: uppercase; cursor: pointer; box-shadow: 0 6px 20px rgba(31,41,55,.28); transition: all .2s; }
.cce-btn-green:hover:not(:disabled)  { transform: translateY(-1px); box-shadow: 0 10px 28px rgba(31,41,55,.38); }
.cce-btn-green:active:not(:disabled) { transform: scale(.97); }
.cce-btn-green:disabled              { opacity: .5; cursor: not-allowed; }

.cce-btn-ghost { display: flex; align-items: center; justify-content: center; padding: 1rem 1.5rem; background: #fff; color: #6b7280; border: 2px solid #e5e7eb; border-radius: 1rem; font-weight: 900; font-size: .7rem; letter-spacing: .15em; text-transform: uppercase; cursor: pointer; transition: all .2s; }
.cce-btn-ghost:hover  { border-color: #d1d5db; color: #374151; }
.cce-btn-ghost:active { transform: scale(.97); }

.cce-x {
  position: absolute; top: 1.25rem; right: 1.25rem; width: 32px; height: 32px;
  background: #f3f4f6; border: none; border-radius: .5rem; color: #9ca3af;
  display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background .2s, color .2s;
}
.cce-x:hover { background: #e5e7eb; color: #1F2937; }

.cce-overlay-enter-active, .cce-overlay-leave-active { transition: opacity .3s ease; }
.cce-overlay-enter-from,   .cce-overlay-leave-to    { opacity: 0; }
.cce-card-enter-active  { transition: all .42s cubic-bezier(.34,1.56,.64,1); }
.cce-card-leave-active  { transition: all .2s ease; }
.cce-card-enter-from    { opacity: 0; transform: scale(.91) translateY(28px); }
.cce-card-leave-to      { opacity: 0; transform: scale(.96) translateY(10px); }
.cce-fade-enter-active, .cce-fade-leave-active { transition: all .22s ease; }
.cce-fade-enter-from,   .cce-fade-leave-to    { opacity: 0; transform: translateY(-4px); }
</style>
