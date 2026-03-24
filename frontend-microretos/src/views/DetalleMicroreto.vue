<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api.js';

const route   = useRoute();
const router  = useRouter();
const reto    = ref(null);
const cargando = ref(true);
const error   = ref(false);
const isLoaded = ref(false);

onMounted(async () => {
  setTimeout(() => { isLoaded.value = true; }, 100);
  try {
    const res = await api.get(`/microretos/${route.params.id}`);
    reto.value = res.data;
  } catch (e) {
    console.error('Error al cargar el microreto:', e);
    error.value = true;
  } finally {
    cargando.value = false;
  }
});

const volver = () => router.push({ name: 'biblioteca' });

// --- NUEVA LÓGICA PARA LA IMAGEN DE FONDO ---
const imagenFondo = computed(() => {
  // Si todavía no hay reto o no tiene familia, devolvemos null (o podrías devolver el logo por defecto)
  if (!reto.value || !reto.value.familia) return null;

  // 1. Convertimos el nombre (ej. "Imagen y Sonido" -> "imagen-y-sonido")
  const slugFamilia = reto.value.familia
    .toLowerCase()
    .normalize("NFD").replace(/[\u0300-\u036f]/g, "") // Quita tildes
    .replace(/\s+/g, '-')                             // Cambia espacios por guiones
    .replace(/[^a-z0-9-]/g, '');                      // Limpia caracteres extraños

  // 2. Construimos la URL usando la variable de entorno de Vite
  // Importante: le quitamos el '/api' del final a tu VITE_API_URL para acceder a la carpeta public
  const baseUrl = import.meta.env.VITE_API_URL.replace(/\/api$/, '');
  
  return `${baseUrl}/familias/${slugFamilia}.webp`;
});
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] font-sans text-[#1F2937]">

    <img
      src="../assets/logo.png"
      alt=""
      aria-hidden="true"
      class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] md:w-[550px] max-w-none pointer-events-none select-none object-contain transition-opacity duration-1000 z-0"
      :class="isLoaded ? 'opacity-20' : 'opacity-0'"
    />

    <!-- Fondo decorativo -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[600px] h-[400px]
                bg-[#99CC33] opacity-5 blur-[120px] rounded-full pointer-events-none z-0" />

    <div class="relative z-10 max-w-5xl mx-auto px-4 py-8 md:px-8 md:py-12">

      <!-- ── Botón volver (cabecera) ── -->
      <div class="mb-8 transition-all duration-700 ease-out"
           :class="isLoaded ? 'translate-y-0 opacity-100' : '-translate-y-4 opacity-0'">
        <button @click="volver"
                class="inline-flex items-center gap-2 px-5 py-2.5
                       bg-white border border-gray-200 rounded-full
                       text-xs font-black uppercase tracking-widest text-[#1F2937]
                       shadow-sm hover:border-[#00A859] hover:text-[#00A859]
                       transition-all active:scale-95">
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                  d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Volver a la Biblioteca
        </button>
      </div>

      <!-- ── CARGANDO ── -->
      <div v-if="cargando" class="flex flex-col items-center justify-center py-32">
        <svg class="animate-spin w-12 h-12 text-[#00A859] mb-4" viewBox="0 0 24 24">
          <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
        </svg>
        <p class="text-[#00A859] font-black tracking-widest uppercase text-sm animate-pulse">
          Cargando ficha técnica...
        </p>
      </div>

      <!-- ── ERROR ── -->
      <div v-else-if="error"
           class="text-center py-32 bg-white rounded-[2rem]
                  border border-dashed border-red-200 shadow-sm">
        <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-5">
          <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3
                     L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
        </div>
        <h3 class="text-[#1F2937] font-black text-2xl mb-2">No se pudo cargar el microreto</h3>
        <p class="text-gray-500 text-sm mb-6">Comprueba tu conexión o vuelve a intentarlo.</p>
        <button @click="volver"
                class="px-6 py-2.5 bg-white border border-gray-200
                       hover:border-[#00A859] hover:text-[#00A859]
                       text-[#1F2937] rounded-full text-xs font-black
                       uppercase tracking-widest transition-all shadow-sm">
          Volver a la Biblioteca
        </button>
      </div>

      <!-- ── FICHA TÉCNICA ── -->
      <template v-else-if="reto">

        <!-- BLOQUE PRINCIPAL -->
        <div class="bg-white rounded-[2rem] shadow-[0_20px_50px_rgb(0,0,0,0.06)]
                    overflow-hidden border border-gray-100
                    transition-all duration-700 delay-100 ease-out"
             :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'">

          <!-- ── Cabecera ── -->
          <div class="relative bg-gray-50 border-b border-gray-100 overflow-hidden">
            
            <div class="absolute inset-0 z-0 pointer-events-none">
              <div class="absolute inset-0 bg-gradient-to-r from-gray-50 via-gray-50/95 to-transparent z-10"></div>
              <div class="absolute inset-0 bg-gradient-to-t from-gray-50 via-transparent to-transparent z-10"></div>
              
              <img
                v-if="imagenFondo"
                :src="imagenFondo"
                alt=""
                class="w-full h-full object-cover object-right opacity-30 md:opacity-40 mix-blend-multiply transition-opacity duration-1000"
                :class="isLoaded ? 'opacity-30 md:opacity-40' : 'opacity-0'"
              />
            </div>

            <div class="relative z-10 px-6 py-8 md:px-14 md:pt-12 md:pb-10 max-w-4xl">

              <p class="text-[#00A859] font-bold text-[10px] tracking-[0.2em] uppercase mb-4
                        flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                           a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                DuaLab · Ficha de Microreto
              </p>

              <h1 class="text-2xl sm:text-3xl md:text-5xl font-black text-[#1F2937]
                         tracking-tight leading-tight mb-3">
                {{ reto.titulo }}
              </h1>
              <h2 class="text-base md:text-xl text-gray-500 font-medium leading-relaxed mb-8">
                {{ reto.subtitulo }}
              </h2>

              <div class="flex flex-wrap gap-2 md:gap-3">
                <span class="flex items-center gap-2 px-3 py-1.5 md:px-4 md:py-2
                             bg-[#1F2937] text-white rounded-lg
                             text-[10px] md:text-xs font-bold uppercase tracking-wider shadow-sm">
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5
                             m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0
                             011-1h2a1 1 0 011 1v5m-4 0h4"/>
                  </svg>
                  {{ reto.empresa_nombre }}
                </span>

                <span v-if="reto.familia"
                      class="flex items-center gap-2 px-3 py-1.5 md:px-4 md:py-2
                             bg-white/80 backdrop-blur-sm border border-gray-200 text-[#1F2937] rounded-lg
                             text-[10px] md:text-xs font-bold uppercase tracking-wider">
                  <svg class="w-3.5 h-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2-2H5a2 2 0 01-2-2v-6
                             a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5
                             a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                  </svg>
                  {{ reto.familia }}
                </span>

                <span v-if="reto.nivel_grupo"
                      class="flex items-center gap-2 px-3 py-1.5 md:px-4 md:py-2
                             bg-white/80 backdrop-blur-sm border border-gray-200 text-gray-500 rounded-lg
                             text-[10px] md:text-xs font-bold uppercase tracking-wider">
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0
                             002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2
                             a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2
                             2h-2a2 2 0 01-2-2z"/>
                  </svg>
                  Nivel {{ reto.nivel_grupo }}
                </span>

                <span v-if="reto.ciclo"
                      class="flex items-center gap-2 px-3 py-1.5 md:px-4 md:py-2
                             bg-[#00A859]/10 backdrop-blur-sm border border-[#00A859]/20 text-[#00A859] rounded-lg
                             text-[10px] md:text-xs font-bold uppercase tracking-wider">
                  <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 14l9-5-9-5-9 5 9 5z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0
                             0112 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0
                             01.665-6.479L12 14z"/>
                  </svg>
                  {{ reto.ciclo }}
                </span>
              </div>
            </div>
          </div>

          <!-- ── Cuerpo ── -->
          <div class="px-6 py-8 md:px-14 md:py-12 space-y-10 md:space-y-14">

            <!-- Quién es / Día a día -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-10">
              <div v-if="reto.quien_es">
                <h3 class="section-title text-[#00A859]">
                  <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0
                             00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0
                             100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001
                             3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                  </svg>
                  ¿Quién es {{ reto.empresa_nombre }}?
                </h3>
                <p class="text-gray-600 text-sm leading-relaxed">{{ reto.quien_es }}</p>
              </div>
              <div v-if="reto.dia_a_dia">
                <h3 class="section-title text-[#00A859]">
                  <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  Su día a día
                </h3>
                <p class="text-gray-600 text-sm leading-relaxed">{{ reto.dia_a_dia }}</p>
              </div>
            </div>

            <!-- Dificultades -->
            <div v-if="reto.dificultades?.length">
              <h3 class="section-title text-yellow-600">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3
                           L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Dificultades
              </h3>
              <ul class="space-y-2 pl-1">
                <li v-for="(item, i) in reto.dificultades" :key="i"
                    class="flex items-start gap-3 text-sm text-gray-700">
                  <span class="text-yellow-500 font-black mt-0.5 shrink-0">•</span>
                  <span>{{ item }}</span>
                </li>
              </ul>
            </div>

            <!-- Pregunta del reto -->
            <div class="bg-gradient-to-r from-gray-50 to-white border-l-4 border-[#00A859]
                        p-6 md:p-8 rounded-r-2xl shadow-sm border-y border-r border-gray-100">
              <h3 class="text-[#00A859] font-black uppercase text-[10px] tracking-[0.2em] mb-3">
                Pregunta del Reto
              </h3>
              <p class="text-lg md:text-2xl font-bold text-[#1F2937] leading-snug">
                {{ reto.pregunta_reto }}
              </p>
            </div>

            <!-- Qué necesitan / Limitaciones -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-10">
              <div v-if="reto.que_necesitan?.length">
                <h3 class="section-title text-[#00A859]">
                  <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0
                             00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                  </svg>
                  Qué necesitan
                </h3>
                <ul class="space-y-2 pl-1">
                  <li v-for="(item, i) in reto.que_necesitan" :key="i"
                      class="flex items-start gap-3 text-sm text-gray-700">
                    <span class="text-[#00A859] font-black mt-0.5 shrink-0">•</span>
                    <span>{{ item }}</span>
                  </li>
                </ul>
              </div>
              <div v-if="reto.limitaciones?.length">
                <h3 class="section-title text-red-500">
                  <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0
                             015.636 5.636m12.728 12.728L5.636 5.636"/>
                  </svg>
                  Limitaciones
                </h3>
                <ul class="space-y-2 pl-1">
                  <li v-for="(item, i) in reto.limitaciones" :key="i"
                      class="flex items-start gap-3 text-sm text-gray-700">
                    <span class="text-red-500 font-black mt-0.5 shrink-0">•</span>
                    <span>{{ item }}</span>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Prototipos / ODS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-10">
              <div v-if="reto.prototipos?.length">
                <h3 class="section-title text-[#00A859]">
                  <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                  </svg>
                  Ejemplos de Prototipos
                </h3>
                <ul class="space-y-2 pl-1">
                  <li v-for="(item, i) in reto.prototipos" :key="i"
                      class="flex items-start gap-3 text-sm text-gray-700">
                    <span class="text-[#00A859] font-black mt-0.5 shrink-0">•</span>
                    <span>{{ item }}</span>
                  </li>
                </ul>
              </div>
              <div v-if="reto.ods_sugeridos?.length">
                <h3 class="section-title text-blue-600">
                  <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 104 0 2 2 0 012-2h1.064
                             M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  ODS Sugeridos
                </h3>
                <ul class="space-y-2 pl-1">
                  <li v-for="ods in reto.ods_sugeridos" :key="ods"
                      class="text-sm font-semibold text-[#1F2937]">{{ ods }}</li>
                </ul>
              </div>
            </div>

            <!-- RA / CE -->
            <div v-if="reto.evaluacion_oficial?.length" class="pt-2">
              <h3 class="flex items-center gap-2 text-[#1F2937] font-bold uppercase text-xs
                         tracking-widest border-b-2 border-gray-200 pb-2 mb-6">
                <svg class="w-5 h-5 text-[#00A859] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 14l9-5-9-5-9 5 9 5z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0
                           0112 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0
                           01.665-6.479L12 14z"/>
                </svg>
                RA/CE Seleccionados
              </h3>
              <div class="space-y-4 md:space-y-6">
                <div v-for="evalObj in reto.evaluacion_oficial" :key="evalObj.modulo"
                     class="bg-white border border-gray-200 p-5 md:p-6 rounded-2xl shadow-sm">
                  <p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Módulo</p>
                  <p class="font-black text-[#1F2937] text-base md:text-lg mb-4">{{ evalObj.modulo }}</p>
                  <div class="mb-4">
                    <p class="text-[10px] uppercase font-bold text-[#00A859] mb-1 flex items-center gap-1">
                      <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/>
                      </svg>
                      Resultado de Aprendizaje
                    </p>
                    <p class="text-sm font-semibold text-gray-700 bg-gray-50 p-4 rounded-xl border border-gray-100">
                      {{ evalObj.ra }}
                    </p>
                  </div>
                  <div class="mb-4">
                    <p class="text-[10px] uppercase font-bold text-gray-500 mb-2">Criterios de Evaluación</p>
                    <ul class="space-y-1.5">
                      <li v-for="(ce, i) in evalObj.ce" :key="i"
                          class="text-sm text-gray-600 flex items-start gap-2">
                        <span class="text-[#00A859] font-bold mt-0.5 shrink-0">✓</span>
                        <span>{{ ce }}</span>
                      </li>
                    </ul>
                  </div>
                  <div v-if="evalObj.aplicacion" class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-sm text-gray-500 italic">
                      <span class="font-bold not-italic text-[#1F2937]">Aplicación: </span>
                      {{ evalObj.aplicacion }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Variantes -->
            <div v-if="reto.variantes?.length" class="pt-2">
              <h3 class="section-title text-[#00A859]">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Variantes
              </h3>
              <ul class="space-y-3">
                <li v-for="(varItem, i) in reto.variantes" :key="i"
                    class="text-sm text-gray-700 bg-gray-50 border border-gray-200 p-4 rounded-xl shadow-sm">
                  <template v-if="varItem.includes(':')">
                    <strong class="text-[#1F2937] block mb-1">{{ varItem.split(':')[0] }}</strong>
                    <span>{{ varItem.substring(varItem.indexOf(':') + 1).trim() }}</span>
                  </template>
                  <template v-else>{{ varItem }}</template>
                </li>
              </ul>
            </div>

          </div>
        </div>

        <!-- GUÍA DOCENTE -->
        <div v-if="reto.tips_profesorado?.length"
             class="relative mt-10 bg-gray-50 rounded-[2rem] shadow-sm
                    border border-gray-200 px-6 py-8 md:px-14 md:py-12
                    transition-all duration-700 delay-200 ease-out"
             :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0'">

          <div class="absolute top-0 right-0 bg-white border-b border-l border-gray-200
                      text-gray-400 px-5 py-2 font-black text-[9px] tracking-widest uppercase
                      rounded-bl-2xl shadow-sm">
            Uso Exclusivo Docente
          </div>

          <h2 class="text-xl md:text-2xl font-black text-[#1F2937] mb-1 mt-2 flex items-center gap-3">
            <svg class="w-6 h-6 text-yellow-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0
                       110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
            </svg>
            Guía de Implementación
          </h2>
          <p class="text-gray-500 text-sm mb-8">
            Recomendaciones pedagógicas para dinamizar el reto.
          </p>

          <div class="grid grid-cols-1 gap-4 md:gap-6">
            <div v-for="(tip, i) in reto.tips_profesorado" :key="i"
                 class="bg-white p-5 md:p-6 rounded-2xl border border-gray-100 shadow-sm">
              <div class="text-sm text-gray-700 leading-relaxed">
                <template v-if="tip.includes(':')">
                  <strong class="text-[#00A859] flex items-center gap-1.5 mb-2
                                 uppercase tracking-wider text-[10px]">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path v-if="i===0" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857
                               M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002
                               5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0
                               014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                      <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ tip.split(':')[0] }}
                  </strong>
                  <span class="block text-gray-600">
                    {{ tip.substring(tip.indexOf(':') + 1).trim() }}
                  </span>
                </template>
                <template v-else>
                  <span class="text-gray-600">{{ tip }}</span>
                </template>
              </div>
            </div>
          </div>
        </div>

        <!-- Botón volver pie -->
        <div class="flex justify-center mt-10 pb-8">
          <button @click="volver"
                  class="inline-flex items-center gap-2 px-8 py-4 bg-white border-2 border-gray-200
                         rounded-full text-xs font-black uppercase tracking-widest text-[#1F2937]
                         shadow-sm hover:border-[#00A859] hover:text-[#00A859] transition-all
                         hover:-translate-y-0.5 active:scale-95">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver a la Biblioteca
          </button>
        </div>

      </template>
    </div>
  </div>
</template>

<style scoped>
.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.65rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  border-bottom: 1px solid #f3f4f6;
  padding-bottom: 8px;
  margin-bottom: 14px;
}
</style>