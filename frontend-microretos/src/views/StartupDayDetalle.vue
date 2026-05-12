<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api.js';
import { useMicroproyectoPdfExport } from '../composables/useMicroproyectoPdfExport.js';

const route  = useRoute();
const router = useRouter();
const proyecto = ref(null);
const cargando = ref(true);
const error    = ref(false);
const isLoaded = ref(false);
const urlCopiada = ref(false);

onMounted(async () => {
  setTimeout(() => { isLoaded.value = true; }, 80);
  try {
    const res = await api.get(`/startup/proyectos/${route.params.uuid}`);
    proyecto.value = res.data;
  } catch {
    error.value = true;
  } finally {
    cargando.value = false;
  }
});

const estadoColor = {
  borrador:  'bg-amber-50 border-amber-200 text-amber-700',
  archivado: 'bg-gray-100 border-gray-200 text-gray-400',
  propuesta: 'bg-[#00A859]/10 border-[#00A859]/20 text-[#00A859]',
  proyecto:  'bg-blue-50 border-blue-200 text-blue-700',
};

function getEstadoKey(p) {
  if (!p) return 'borrador';
  if (p.estado === 'borrador')  return 'borrador';
  if (p.estado === 'archivado') return 'archivado';
  return p.empresa_validado ? 'proyecto' : 'propuesta';
}
const estadoLabel = { borrador: 'Borrador', archivado: 'Archivado', propuesta: 'Propuesta', proyecto: 'Proyecto' };

const landingUrl = computed(() => {
  if (!proyecto.value?.token_empresa) return '';
  const isLocal = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
  const base = isLocal ? window.location.origin : 'https://dualab.es';
  return `${base}/startup/landing/${proyecto.value.token_empresa}`;
});

async function copiarUrl() {
  await navigator.clipboard.writeText(landingUrl.value);
  urlCopiada.value = true;
  setTimeout(() => { urlCopiada.value = false; }, 2000);
}

const { descargarPDF } = useMicroproyectoPdfExport();

async function archivar() {
  if (!confirm('¿Archivar este proyecto?')) return;
  await api.put(`/startup/proyectos/${proyecto.value.uuid}`, { estado: 'archivado' });
  proyecto.value.estado = 'archivado';
}
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] p-4 md:p-10 font-sans text-[#1F2937]">

    <!-- Fondo decorativo -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[700px] h-[400px]
                bg-[#99CC33] opacity-5 blur-[120px] rounded-full pointer-events-none z-0" />

    <div class="relative z-10 max-w-4xl mx-auto"
         :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
         style="transition: opacity 0.4s ease, transform 0.4s ease">

      <!-- Cargando -->
      <div v-if="cargando" class="flex flex-col items-center justify-center py-32">
        <svg class="animate-spin w-12 h-12 text-[#00A859] mb-4" viewBox="0 0 24 24">
          <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
        </svg>
        <p class="text-[#00A859] font-black tracking-widest uppercase text-sm animate-pulse">Cargando...</p>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="text-center py-32">
        <p class="text-gray-400 text-sm mb-4">No se pudo cargar el proyecto.</p>
        <button @click="router.push({ name: 'startup-day' })"
                class="text-[#00A859] text-sm font-bold hover:underline">← Volver a la lista</button>
      </div>

      <template v-else-if="proyecto">

        <!-- Cabecera -->
        <header class="mb-8 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
          <div>
            <div class="flex items-center gap-3 mb-3">
              <button @click="router.push({ name: 'startup-day' })"
                      class="w-8 h-8 rounded-full bg-white border border-gray-200 shadow-sm flex items-center justify-center
                             text-gray-400 hover:text-[#00A859] hover:border-[#00A859]/30 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
              </button>
              <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full
                          bg-[#00A859]/10 border border-[#00A859]/20">
                <span class="w-2 h-2 rounded-full bg-[#00A859]" />
                <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">StartUp Day</span>
              </div>
              <span :class="['text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full border', estadoColor[getEstadoKey(proyecto)]]">
                {{ estadoLabel[getEstadoKey(proyecto)] }}
              </span>
            </div>
            <h1 class="text-2xl md:text-3xl font-black tracking-tight text-[#121212]">{{ proyecto.titulo }}</h1>
            <div class="flex flex-wrap gap-x-3 gap-y-1 mt-2 text-xs text-gray-400">
              <span v-if="proyecto.empresa_nombre">{{ proyecto.empresa_nombre }}</span>
              <span v-if="proyecto.centro_nombre">· {{ proyecto.centro_nombre }}</span>
              <span v-if="proyecto.ciclo_nombre">· {{ proyecto.ciclo_nombre }}</span>
            </div>
          </div>

          <div class="flex gap-2 shrink-0">
            <button
              @click="descargarPDF(proyecto)"
              class="px-4 py-2 bg-[#00A859] rounded-full text-xs font-black
                     uppercase tracking-widest text-white shadow-sm
                     hover:bg-[#009048] transition-all flex items-center gap-1.5"
              title="Descargar ficha PDF"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                      d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a1 1 0 001 1h14a1 1 0 001-1v-2"/>
              </svg>
              PDF
            </button>
            <button
              @click="router.push({ name: 'startup-day-editar', params: { uuid: proyecto.uuid } })"
              class="px-4 py-2 bg-white border border-gray-200 rounded-full text-xs font-black
                     uppercase tracking-widest text-gray-500 shadow-sm
                     hover:border-[#00A859] hover:text-[#00A859] transition-all"
            >
              Editar
            </button>
            <button v-if="proyecto.estado !== 'archivado'"
                    @click="archivar"
                    class="px-4 py-2 bg-white border border-gray-200 rounded-full text-xs font-black
                           uppercase tracking-widest text-gray-400 shadow-sm
                           hover:border-red-200 hover:text-red-400 transition-all">
              Archivar
            </button>
          </div>
        </header>

        <!-- Link empresa (solo si publicado) -->
        <div v-if="proyecto.estado === 'publicado'"
             class="bg-[#00A859]/5 border border-[#00A859]/20 rounded-2xl p-5 mb-6">
          <div class="flex items-center justify-between gap-4">
            <div class="min-w-0">
              <p class="text-[10px] font-black uppercase tracking-widest text-[#00A859] mb-1">Enlace de validación empresa</p>
              <p class="text-xs text-gray-400 break-all">{{ landingUrl }}</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <button v-if="proyecto.empresa_id && !proyecto.empresa_validado"
                      @click="router.push({ name: 'empresas', query: { empresa_id: proyecto.empresa_id, proyecto_uuid: proyecto.uuid, panel: 'validacion' } })"
                      class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold uppercase
                             tracking-wider border bg-amber-50 text-amber-700 border-amber-200
                             hover:bg-amber-100 transition-all">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Enviar a empresa
              </button>
              <button @click="copiarUrl"
                      :class="['px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all border',
                                urlCopiada
                                  ? 'bg-[#00A859]/10 text-[#00A859] border-[#00A859]/20'
                                  : 'bg-white text-gray-500 border-gray-200 hover:border-[#00A859] hover:text-[#00A859]']">
                {{ urlCopiada ? '¡Copiado!' : 'Copiar enlace' }}
              </button>
            </div>
          </div>

          <div v-if="proyecto.empresa_validado" class="flex items-center gap-2 mt-3 pt-3 border-t border-[#00A859]/15">
            <svg class="w-4 h-4 text-[#00A859] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-sm font-bold text-[#00A859]">La empresa ha validado el proyecto</p>
          </div>
        </div>

        <!-- Secciones del proyecto -->
        <div class="grid gap-4 sm:grid-cols-2">

          <!-- Empresa -->
          <div v-if="proyecto.datos_empresa?.nombre" class="card-section">
            <p class="section-label">Empresa</p>
            <p class="text-sm font-bold text-[#1F2937] mb-1">{{ proyecto.datos_empresa.nombre }}</p>
            <div class="space-y-0.5 text-xs text-gray-400">
              <p v-if="proyecto.datos_empresa.sector">{{ proyecto.datos_empresa.sector }}</p>
              <p v-if="proyecto.datos_empresa.persona_contacto">{{ proyecto.datos_empresa.persona_contacto }}</p>
              <p v-if="proyecto.datos_empresa.email">{{ proyecto.datos_empresa.email }}</p>
              <p v-if="proyecto.datos_empresa.descripcion" class="mt-2 leading-relaxed text-gray-500">
                {{ proyecto.datos_empresa.descripcion }}
              </p>
            </div>
          </div>

          <!-- Equipo -->
          <div v-if="proyecto.equipo?.alumnos?.length" class="card-section">
            <p class="section-label">Equipo ({{ proyecto.equipo.alumnos.length }} personas)</p>
            <div class="flex flex-wrap gap-1.5">
              <span v-for="a in proyecto.equipo.alumnos" :key="a.nombre"
                    class="text-xs bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-full text-gray-600">
                {{ a.nombre }}<span v-if="a.rol" class="text-gray-400"> · {{ a.rol }}</span>
              </span>
            </div>
          </div>

          <!-- El reto -->
          <div v-if="proyecto.diseno_reto?.descripcion" class="card-section sm:col-span-2">
            <p class="section-label">El reto</p>
            <p v-if="proyecto.diseno_reto.pregunta_reto"
               class="text-sm font-bold text-[#00A859] mb-2 italic">
              "{{ proyecto.diseno_reto.pregunta_reto }}"
            </p>
            <p class="text-sm text-gray-600 leading-relaxed">{{ proyecto.diseno_reto.descripcion }}</p>
            <div v-if="proyecto.diseno_reto.entregables" class="mt-3 pt-3 border-t border-gray-100">
              <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1">Entregables</p>
              <p class="text-xs text-gray-500">{{ proyecto.diseno_reto.entregables }}</p>
            </div>
          </div>

          <!-- Módulos -->
          <div v-if="proyecto.modulos_seleccionados?.length" class="card-section">
            <p class="section-label">Módulos ({{ proyecto.modulos_seleccionados.length }})</p>
            <div class="flex flex-wrap gap-1.5">
              <span v-for="m in proyecto.modulos_seleccionados" :key="m.id"
                    class="text-xs bg-[#00A859]/8 border border-[#00A859]/15 text-[#00A859] px-2.5 py-1 rounded-full">
                {{ m.nombre }}
              </span>
            </div>
          </div>

          <!-- Fases -->
          <div v-if="proyecto.diseno_microproyecto?.fases?.length" class="card-section">
            <p class="section-label">Fases del proyecto</p>
            <ol class="space-y-2.5">
              <li v-for="(f, i) in proyecto.diseno_microproyecto.fases" :key="i"
                  class="flex items-start gap-2.5 text-sm">
                <span class="w-5 h-5 rounded-full bg-[#00A859]/10 text-[#00A859] font-black text-[10px]
                             flex items-center justify-center shrink-0 mt-0.5">{{ i + 1 }}</span>
                <div>
                  <p class="font-bold text-[#1F2937]">{{ f.nombre }}
                    <span v-if="f.duracion" class="text-gray-400 font-normal text-xs"> · {{ f.duracion }}</span>
                  </p>
                  <p v-if="f.descripcion" class="text-gray-400 text-xs mt-0.5">{{ f.descripcion }}</p>
                </div>
              </li>
            </ol>
          </div>

          <!-- Objetivos -->
          <div v-if="proyecto.objetivos?.lista?.length" class="card-section">
            <p class="section-label">Objetivos</p>
            <ul class="space-y-1.5">
              <li v-for="obj in proyecto.objetivos.lista" :key="obj"
                  class="flex items-start gap-2 text-sm text-gray-600">
                <span class="text-[#00A859] shrink-0 mt-0.5 font-bold">›</span> {{ obj }}
              </li>
            </ul>
          </div>

          <!-- KPIs -->
          <div v-if="proyecto.kpis?.lista?.length" class="card-section">
            <p class="section-label">KPIs</p>
            <ul class="space-y-1.5">
              <li v-for="kpi in proyecto.kpis.lista" :key="kpi"
                  class="flex items-start gap-2 text-sm text-gray-600">
                <span class="text-[#00A859] shrink-0 mt-0.5">✓</span> {{ kpi }}
              </li>
            </ul>
          </div>

          <!-- Validación empresa -->
          <div v-if="proyecto.validacion_empresa?.respuestas" class="card-section sm:col-span-2">
            <p class="section-label text-[#00A859]">Respuestas empresa</p>
            <div class="grid sm:grid-cols-2 gap-3">
              <div v-for="(val, key) in proyecto.validacion_empresa.respuestas" :key="key"
                   class="bg-gray-50 border border-gray-100 rounded-xl px-3 py-2.5">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">{{ key.replace(/_/g, ' ') }}</p>
                <p class="text-sm font-bold"
                   :class="val === 'Sí' ? 'text-[#00A859]' : val === 'No' ? 'text-red-500' : 'text-amber-600'">
                  {{ val }}
                </p>
              </div>
            </div>
            <p v-if="proyecto.validacion_empresa.comentarios"
               class="mt-3 text-sm text-gray-500 italic border-t border-gray-100 pt-3">
              "{{ proyecto.validacion_empresa.comentarios }}"
            </p>
          </div>

          <!-- Resumen -->
          <div v-if="proyecto.resumen?.texto" class="card-section sm:col-span-2">
            <p class="section-label">Resumen ejecutivo</p>
            <p class="text-sm text-gray-600 leading-relaxed">{{ proyecto.resumen.texto }}</p>
          </div>

        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
@reference "../style.css";

.card-section {
  @apply bg-white border border-gray-100 rounded-[1.5rem] shadow-sm p-5 space-y-3;
}
.section-label {
  @apply text-[10px] font-black uppercase tracking-[0.2em] text-gray-400;
}
</style>
