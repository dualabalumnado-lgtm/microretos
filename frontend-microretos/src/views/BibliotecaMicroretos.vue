<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../api.js';

const microretos = ref([]);
const cargando = ref(true);

// Nuevos Filtros solicitados
const filtroCentro = ref('');
const filtroFamilia = ref('');
const filtroCiclo = ref('');

// Extraer opciones únicas en cascada
const centrosDisponibles = computed(() => {
  const centros = microretos.value.map(m => m.centro_educativo || m.centro).filter(Boolean);
  return [...new Set(centros)].sort();
});

const familiasDisponibles = computed(() => {
  let datos = microretos.value;
  if (filtroCentro.value) {
    datos = datos.filter(m => (m.centro_educativo || m.centro) === filtroCentro.value);
  }
  const familias = datos.map(m => m.familia).filter(Boolean);
  return [...new Set(familias)].sort();
});

const ciclosDisponibles = computed(() => {
  let datos = microretos.value;
  if (filtroCentro.value) {
    datos = datos.filter(m => (m.centro_educativo || m.centro) === filtroCentro.value);
  }
  if (filtroFamilia.value) {
    datos = datos.filter(m => m.familia === filtroFamilia.value);
  }
  const ciclos = datos.map(m => m.ciclo).filter(Boolean);
  return [...new Set(ciclos)].sort();
});

// Lógica de filtrado
const microretosFiltrados = computed(() => {
  return microretos.value.filter(reto => {
    const centroReto = reto.centro_educativo || reto.centro;
    const coincideCentro = filtroCentro.value === '' || centroReto === filtroCentro.value;
    const coincideFamilia = filtroFamilia.value === '' || reto.familia === filtroFamilia.value;
    const coincideCiclo = filtroCiclo.value === '' || reto.ciclo === filtroCiclo.value;
    return coincideCentro && coincideFamilia && coincideCiclo;
  });
});

onMounted(async () => {
  try {
    const res = await api.get('/microretos');
    microretos.value = res.data;
  } catch (error) {
    console.error("Error al cargar la biblioteca:", error);
  } finally {
    cargando.value = false;
  }
});

const limpiarFiltros = () => {
  filtroCentro.value = '';
  filtroFamilia.value = '';
  filtroCiclo.value = '';
};
</script>

<template>
  <div class="min-h-screen bg-[#121212] p-4 md:p-12 transition-colors duration-500 font-sans text-[#D9D9D9] relative overflow-hidden">
    
    <div class="absolute top-[-10%] left-1/2 transform -translate-x-1/2 w-[800px] h-[500px] bg-[#99CC33] opacity-[0.03] blur-[120px] rounded-full pointer-events-none"></div>

    <div class="max-w-7xl mx-auto relative z-10">
      <header class="mb-10 text-center">
        <div class="inline-flex items-center mb-8 bg-[#1F2937] py-4 pr-10 pl-6 rounded-[3rem] shadow-lg border border-[#333333]">
          <img src="../assets/logo.png" alt="Logo DuaLab" class="h-32 md:h-40 w-auto object-contain -mr-4 md:-mr-8 relative z-10" />
          <span class="font-black text-4xl md:text-5xl tracking-tighter uppercase text-white italic relative z-20">
            DuaLab <span class="text-[#99CC33] not-italic text-lg md:text-xl ml-1">Studio Tool</span>
          </span>
        </div>
        <h1 class="text-5xl font-black tracking-tight mb-4 text-[#99CC33]">
          Explorador de Micro-Retos
        </h1>
        <p class="text-[#A0AAB5] max-w-2xl mx-auto text-base leading-relaxed italic">
          Encuentra, filtra y reutiliza los desafíos generados por la Inteligencia Artificial para tus alumnos.
        </p>
      </header>



      <section class="bg-[#1a1a1a]/90 backdrop-blur-md rounded-[2rem] p-6 md:p-8 border border-[#333333] shadow-2xl mb-14">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 items-end">
          
          <div>
            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-[#00A859] ml-2 mb-2 block flex items-center gap-2">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
              Centro Educativo
            </label>
            <select v-model="filtroCentro" @change="filtroFamilia = ''; filtroCiclo = ''" class="w-full bg-[#121212] border border-[#333333] rounded-xl p-3.5 text-sm font-bold text-[#F2F2F2] focus:border-[#99CC33] focus:ring-1 focus:ring-[#99CC33] outline-none transition-all shadow-inner hover:border-[#444]">
              <option value="">Todos los centros...</option>
              <option v-for="centro in centrosDisponibles" :key="centro" :value="centro">{{ centro }}</option>
            </select>
          </div>

          <div>
            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-[#00A859] ml-2 mb-2 block">Familia Profesional</label>
            <select v-model="filtroFamilia" @change="filtroCiclo = ''" class="w-full bg-[#121212] border border-[#333333] rounded-xl p-3.5 text-sm font-bold text-[#F2F2F2] focus:border-[#99CC33] focus:ring-1 focus:ring-[#99CC33] outline-none transition-all shadow-inner hover:border-[#444] disabled:opacity-50 disabled:cursor-not-allowed" :disabled="familiasDisponibles.length === 0">
              <option value="">Todas las familias...</option>
              <option v-for="familia in familiasDisponibles" :key="familia" :value="familia">{{ familia }}</option>
            </select>
          </div>

          <div>
            <label class="text-[10px] font-black uppercase tracking-[0.2em] text-[#00A859] ml-2 mb-2 block">Ciclo Formativo</label>
            <select v-model="filtroCiclo" class="w-full bg-[#121212] border border-[#333333] rounded-xl p-3.5 text-sm font-bold text-[#F2F2F2] focus:border-[#99CC33] focus:ring-1 focus:ring-[#99CC33] outline-none transition-all shadow-inner hover:border-[#444] disabled:opacity-50 disabled:cursor-not-allowed" :disabled="ciclosDisponibles.length === 0">
              <option value="">Todos los ciclos...</option>
              <option v-for="ciclo in ciclosDisponibles" :key="ciclo" :value="ciclo">{{ ciclo }}</option>
            </select>
          </div>

          <div>
            <button @click="limpiarFiltros" :disabled="!filtroCentro && !filtroFamilia && !filtroCiclo" 
              class="w-full py-3.5 rounded-xl font-bold text-xs tracking-widest uppercase transition-all border flex items-center justify-center gap-2"
              :class="(filtroCentro || filtroFamilia || filtroCiclo) ? 'bg-[#99CC33]/10 text-[#99CC33] border-[#99CC33]/50 hover:bg-[#99CC33] hover:text-[#121212]' : 'bg-[#121212] text-[#666] border-[#333] opacity-50 cursor-not-allowed'">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h16v2a2 2 0 01-.586 1.414l-4.828 4.828A2 2 0 0014 13.657v4.586l-4 2v-6.586a2 2 0 00-.586-1.414L4.586 7.414A2 2 0 014 6V4z"/></svg>
              Quitar Filtros
            </button>
          </div>

        </div>
      </section>

      <div v-if="cargando" class="flex flex-col items-center justify-center py-20">
        <svg class="animate-spin w-12 h-12 text-[#99CC33] mb-4" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/></svg>
        <p class="text-[#99CC33] font-black tracking-widest uppercase text-sm animate-pulse">Cargando Biblioteca...</p>
      </div>

      <div v-else-if="microretosFiltrados.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="reto in microretosFiltrados" :key="reto.id" 
             class="bg-[#1a1a1a] rounded-[1.5rem] border border-[#333] hover:border-[#99CC33] hover:shadow-[0_0_20px_rgba(153,204,51,0.15)] transition-all duration-300 flex flex-col group relative overflow-hidden transform hover:-translate-y-1">
          
          <div class="absolute top-4 right-4 bg-[#00A859]/10 border border-[#00A859]/30 text-[#00A859] px-3 py-1 text-[9px] font-black uppercase tracking-widest rounded-full backdrop-blur-sm">
            {{ reto.nivel_grupo || 'Nivel ND' }}
          </div>

          <div class="p-7 flex-1 flex flex-col pt-10">
            <h3 class="text-white! font-black text-xl leading-tight mb-3 group-hover:text-[#99CC33] transition-colors line-clamp-2" :title="reto.titulo">
              {{ reto.titulo }}
            </h3>
            
            <div class="flex flex-col gap-1.5 mb-5 border-l-2 border-[#333] pl-3">
              <p class="text-[#99CC33] text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                {{ reto.empresa_nombre }}
              </p>
              <p class="text-[#888] text-[10px] font-bold uppercase tracking-wider flex items-center gap-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                {{ reto.centro_educativo || 'Centro ND' }}
              </p>
            </div>

            <p class="text-[#AAA] text-sm leading-relaxed line-clamp-3 mb-6 flex-1 font-light">
              {{ reto.pregunta_reto }}
            </p>

            <div class="mt-auto">
              <span class="inline-block bg-[#121212] text-[#D9D9D9] border border-[#333] px-3 py-1.5 rounded-lg text-xs font-medium truncate max-w-full shadow-sm" :title="reto.ciclo">
                {{ reto.ciclo }}
              </span>
            </div>
          </div>
          
          <button class="w-full bg-[#121212] group-hover:bg-[#99CC33] text-[#99CC33] group-hover:text-[#121212] font-black text-xs uppercase tracking-widest py-4 transition-all duration-300 border-t border-[#333] flex items-center justify-center gap-2">
            Ver Ficha Técnica 
            <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
          </button>
        </div>
      </div>

      <div v-else class="text-center py-20 bg-[#1a1a1a] rounded-[2rem] border border-dashed border-[#444] shadow-inner">
        <div class="w-20 h-20 bg-[#121212] rounded-full flex items-center justify-center mx-auto mb-5 border border-[#333]">
          <svg class="w-10 h-10 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 class="text-white font-black text-2xl mb-2">No hay resultados</h3>
        <p class="text-[#888] text-sm max-w-md mx-auto">Prueba a limpiar los filtros o genera nuevos microretos en el Estudio interactivo.</p>
        <button @click="limpiarFiltros" class="mt-6 px-6 py-2 bg-[#121212] border border-[#333] hover:border-[#99CC33] text-[#99CC33] rounded-full text-xs font-bold uppercase tracking-widest transition-colors">
          Restablecer Búsqueda
        </button>
      </div>

    </div>
  </div>
</template>

<style scoped>
@import "tailwindcss";

:global(body) {
  background-color: #121212 !important; 
  color: #D9D9D9 !important; 
}

/* Scrollbar personalizada para select (Opcional, mejora visual en webkit) */
::-webkit-scrollbar {
  width: 8px;
}
::-webkit-scrollbar-track {
  background: #121212; 
}
::-webkit-scrollbar-thumb {
  background: #333; 
  border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
  background: #99CC33; 
}
</style>