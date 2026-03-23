<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth'
import api from '../api.js';
import LoginModal from '../components/LoginModal.vue'; 

const router = useRouter();
const isLoaded = ref(false);
const microretos = ref([]);
const cargando = ref(true);
const familias = ref([]);
const showLogin = ref(false);
const accionPendiente = ref(null);

const familiaSeleccionada = ref(null);

const authStore = useAuthStore()

// filtroCentro ahora actúa en AMBAS capas (familias y microretos)
const filtroCentro = ref('');
const filtroCiclo = ref('');

const centrosDisponibles = computed(() => {
  const centros = microretos.value.map(m => m.centro_educativo || m.centro).filter(Boolean);
  return [...new Set(centros)].sort();
});

const ciclosDisponibles = computed(() => {
  let datos = microretos.value.filter(m => m.familia === familiaSeleccionada.value);
  if (filtroCentro.value) {
    datos = datos.filter(m => (m.centro_educativo || m.centro) === filtroCentro.value);
  }
  return [...new Set(datos.map(m => m.ciclo).filter(Boolean))].sort();
});

const microretosFiltrados = computed(() => {
  return microretos.value.filter(reto => {
    const centroReto = reto.centro_educativo || reto.centro;
    return (
      reto.familia === familiaSeleccionada.value &&
      (filtroCentro.value === '' || centroReto === filtroCentro.value) &&
      (filtroCiclo.value === '' || reto.ciclo === filtroCiclo.value)
    );
  });
});

// Solo cuenta retos del centro seleccionado para las tarjetas de familia
const conteoPorFamilia = computed(() => {
  const mapa = {};
  const datos = filtroCentro.value
    ? microretos.value.filter(m => (m.centro_educativo || m.centro) === filtroCentro.value)
    : microretos.value;
  datos.forEach(m => {
    if (m.familia) mapa[m.familia] = (mapa[m.familia] || 0) + 1;
  });
  return mapa;
});

// Familias que tienen al menos 1 reto en el centro seleccionado
const familiasFiltradas = computed(() => {
  if (!filtroCentro.value) return familias.value;
  return familias.value.filter(f => (conteoPorFamilia.value[f.nombre] || 0) > 0);
});

onMounted(async () => {
  setTimeout(() => { isLoaded.value = true; }, 100);
  try {
    const [resMicroretos, resFamilias] = await Promise.all([
      api.get('/microretos'),
      api.get('/familias'),
    ]);
    microretos.value = resMicroretos.data;
    familias.value = resFamilias.data.map(f =>
      typeof f === 'string' ? { nombre: f, imagen_url: null } : f
    );

    // Establecer el primer centro disponible como valor por defecto
    const centros = [...new Set(
      resMicroretos.data.map(m => m.centro_educativo || m.centro).filter(Boolean)
    )].sort();
    if (centros.length > 0) {
      filtroCentro.value = centros[0];
    }
  } catch (error) {
    console.error('Error al cargar la biblioteca:', error);
  } finally {
    cargando.value = false;
  }
});

const seleccionarFamilia = (nombre) => {
  familiaSeleccionada.value = nombre;
  filtroCiclo.value = '';
  // filtroCentro se mantiene intacto al entrar en una familia
};

const volverAFamilias = () => {
  familiaSeleccionada.value = null;
  filtroCiclo.value = '';
  // filtroCentro se mantiene al volver, para no perder la selección
};

const limpiarFiltros = () => {
  filtroCiclo.value = '';
};

const estaAutenticado = () => authStore.isAuthenticated

const irADetalle = (reto) => {
  if (!estaAutenticado()) {
    accionPendiente.value = { tipo: 'ver', payload: reto };
    showLogin.value = true;
    return;
  }
  router.push({ name: 'detalle-microreto', params: { id: reto.id } });
};

const onLoginSuccess = () => {
  if (!accionPendiente.value) return;
  const { tipo, payload } = accionPendiente.value;
  accionPendiente.value = null;

  if (tipo === 'eliminar') {
    retoAEliminar.value = payload;
    modalVisible.value = true;
  } else if (tipo === 'ver') {
    router.push({ name: 'detalle-microreto', params: { id: payload.id } });
  }
};

// --- MODAL ---
const modalVisible = ref(false);
const retoAEliminar = ref(null);

const abrirModalEliminar = (reto) => {
  if (!estaAutenticado()) {
    accionPendiente.value = { tipo: 'eliminar', payload: reto };
    showLogin.value = true;
    return;
  }
  retoAEliminar.value = reto;
  modalVisible.value = true;
};

const cancelarEliminar = () => {
  modalVisible.value = false;
  retoAEliminar.value = null;
};

const confirmarEliminar = async () => {
  if (!retoAEliminar.value) return;
  try {
    await api.delete(`/microretos/${retoAEliminar.value.id}`);
    microretos.value = microretos.value.filter(m => m.id !== retoAEliminar.value.id);
  } catch (error) {
    console.error('Error al eliminar el microreto:', error);
  } finally {
    cancelarEliminar();
  }
};
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] p-4 md:p-12 font-sans text-[#1F2937] relative overflow-hidden">

    <div
      class="absolute top-[-10%] left-1/2 transform -translate-x-1/2 w-[800px] h-[500px] bg-[#99CC33] blur-[120px] rounded-full pointer-events-none transition-opacity duration-1000"
      :class="isLoaded ? 'opacity-10' : 'opacity-0'">
    </div>

    <div class="max-w-7xl mx-auto relative z-10">

      <!-- HEADER -->
      <header class="mb-10 text-center flex flex-col items-center">
        <div
          class="inline-flex items-center mb-8 bg-[#1F2937] py-4 pr-10 pl-6 rounded-[3rem] shadow-lg border border-[#333333] transition-all duration-1000 ease-out transform"
          :class="isLoaded ? 'translate-y-0 opacity-100' : '-translate-y-10 opacity-0'">
          <img src="../assets/logo.png" alt="Logo DuaLab"
            class="h-32 md:h-40 w-auto object-contain -mr-4 md:-mr-8 relative z-10" />
          <span class="font-black text-4xl md:text-5xl tracking-tighter uppercase text-white italic relative z-20">
            Dua<span class="text-[#00A859]">Lab</span>
            <span class="text-[#99CC33] not-italic text-lg md:text-xl ml-1">Library</span>
          </span>
        </div>
        <h1
          class="text-4xl md:text-5xl font-black tracking-tight mb-4 text-[#121212] transition-all duration-1000 delay-150 ease-out transform"
          :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'">
          Explorador de
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00A859] to-[#99CC33]">Micro-Retos</span>
        </h1>
        <p class="text-gray-500 max-w-2xl mx-auto text-base md:text-lg leading-relaxed font-medium transition-all duration-1000 delay-300 ease-out transform"
          :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'">
          Encuentra, filtra y reutiliza los desafíos generados por la Inteligencia Artificial para tus alumnos.
        </p>
      </header>

      <!-- CARGANDO -->
      <div v-if="cargando" class="flex flex-col items-center justify-center py-20">
        <svg class="animate-spin w-12 h-12 text-[#00A859] mb-4" viewBox="0 0 24 24">
          <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z" />
        </svg>
        <p class="text-[#00A859] font-black tracking-widest uppercase text-sm animate-pulse">Cargando Biblioteca...</p>
      </div>

      <template v-else>

        <!-- ============================================ -->
        <!-- SELECTOR DE CENTRO (visible en ambas capas) -->
        <!-- ============================================ -->
        <div
          class="mb-8 transition-all duration-1000 delay-400 ease-out transform"
          :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'">
          <div class="bg-white/90 backdrop-blur-md rounded-[2rem] p-5 border border-gray-100 shadow-[0_20px_50px_rgb(0,0,0,0.04)]">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
              <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 flex items-center gap-2 whitespace-nowrap">
                <svg class="w-3.5 h-3.5 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Centro Educativo
              </label>
              <div class="flex flex-wrap gap-2 flex-1">
                <button
                  v-for="centro in centrosDisponibles"
                  :key="centro"
                  @click="() => { filtroCentro = centro; familiaSeleccionada = null; filtroCiclo = ''; }"
                  class="px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest transition-all duration-200 border"
                  :class="filtroCentro === centro
                    ? 'bg-[#00A859] text-white border-[#00A859] shadow-md'
                    : 'bg-gray-50 text-gray-500 border-gray-200 hover:border-[#00A859] hover:text-[#00A859]'">
                  {{ centro }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ================================ -->
        <!-- CAPA 1: TARJETAS DE FAMILIAS     -->
        <!-- ================================ -->
        <Transition name="slide-up" mode="out-in">
          <div v-if="!familiaSeleccionada" key="familias"
            class="transition-all duration-1000 delay-500 ease-out transform"
            :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'">

            <p class="text-center text-gray-400 text-sm font-bold uppercase tracking-widest mb-8">
              Selecciona una familia profesional para explorar sus micro-retos
            </p>

            <div v-if="familiasFiltradas.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
              <button
                v-for="familia in familiasFiltradas"
                :key="familia.nombre"
                @click="seleccionarFamilia(familia.nombre)"
                class="group relative rounded-[1.5rem] overflow-hidden border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 bg-white text-left focus:outline-none focus:ring-2 focus:ring-[#00A859]/40">

                <div class="relative h-44 overflow-hidden">
                  <div class="w-full h-full bg-gradient-to-br from-[#00A859]/10 via-[#99CC33]/10 to-gray-100 flex items-center justify-center group-hover:from-[#00A859]/20 group-hover:via-[#99CC33]/15 transition-all duration-300">
                    <svg class="w-16 h-16 text-[#00A859]/30 group-hover:text-[#00A859]/50 transition-colors duration-300"
                      fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                        d="M12 14l9-5-9-5-9 5 9 5zm0 7V14m0 0l-6.16-3.422M12 21a11.952 11.952 0 01-5.835-6.578" />
                    </svg>
                  </div>
                  <div class="absolute top-3 right-3 bg-[#00A859] text-white text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full shadow">
                    {{ conteoPorFamilia[familia.nombre] || 0 }} reto{{ (conteoPorFamilia[familia.nombre] || 0) !== 1 ? 's' : '' }}
                  </div>
                </div>

                <div class="p-5">
                  <h3 class="font-black text-[#1F2937] text-base leading-tight mb-3 group-hover:text-[#00A859] transition-colors line-clamp-2">
                    {{ familia.nombre }}
                  </h3>
                  <div class="flex items-center gap-2 text-[#00A859] text-xs font-black uppercase tracking-widest">
                    <span>Explorar</span>
                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-1 transition-transform"
                      fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                  </div>
                </div>
              </button>
            </div>

            <!-- Sin familias para este centro -->
            <div v-else class="text-center py-20 bg-white rounded-[2rem] border border-dashed border-gray-300 shadow-sm">
              <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-5 border border-gray-100 shadow-inner">
                <svg class="w-10 h-10 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 class="text-[#1F2937] font-black text-2xl mb-2">Sin micro-retos para este centro</h3>
              <p class="text-gray-500 text-sm max-w-md mx-auto">Este centro todavía no tiene micro-retos generados.</p>
            </div>

          </div>
        </Transition>

        <!-- ================================ -->
        <!-- CAPA 2: MICRORETOS DE LA FAMILIA -->
        <!-- ================================ -->
        <Transition name="slide-up" mode="out-in">
          <div v-if="familiaSeleccionada" key="microretos">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
              <div class="flex items-center gap-4">
                <button @click="volverAFamilias"
                  class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-gray-500 hover:text-[#00A859] transition-colors group">
                  <svg class="w-4 h-4 transform group-hover:-translate-x-1 transition-transform"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                  </svg>
                  Todas las familias
                </button>
                <span class="text-gray-200">|</span>
                <h2 class="text-xl font-black text-[#1F2937]">{{ familiaSeleccionada }}</h2>
              </div>
              <span class="text-xs text-gray-400 font-bold">
                {{ microretosFiltrados.length }} micro-reto{{ microretosFiltrados.length !== 1 ? 's' : '' }}
              </span>
            </div>

            <!-- Filtro de Ciclo (el de centro ya está arriba, siempre visible) -->
            <section class="bg-white/90 backdrop-blur-md rounded-[2rem] p-6 border border-gray-100 shadow-[0_20px_50px_rgb(0,0,0,0.04)] mb-10">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5 items-end">

                <div>
                  <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ml-2 mb-2 block">
                    Ciclo Formativo
                  </label>
                  <select v-model="filtroCiclo"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3.5 text-sm font-bold text-[#1F2937] focus:bg-white focus:border-[#00A859] focus:ring-2 focus:ring-[#00A859]/10 outline-none transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="ciclosDisponibles.length === 0">
                    <option value="">Todos los ciclos...</option>
                    <option v-for="ciclo in ciclosDisponibles" :key="ciclo" :value="ciclo">{{ ciclo }}</option>
                  </select>
                </div>

                <div>
                  <button @click="limpiarFiltros" :disabled="!filtroCiclo"
                    class="w-full py-3.5 rounded-xl font-bold text-xs tracking-widest uppercase transition-all border flex items-center justify-center gap-2 shadow-sm"
                    :class="filtroCiclo
                      ? 'bg-white text-red-500 border-red-200 hover:bg-red-50 hover:border-red-400'
                      : 'bg-gray-50 text-gray-400 border-gray-200 opacity-60 cursor-not-allowed'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4h16v2a2 2 0 01-.586 1.414l-4.828 4.828A2 2 0 0014 13.657v4.586l-4 2v-6.586a2 2 0 00-.586-1.414L4.586 7.414A2 2 0 014 6V4z" />
                    </svg>
                    Quitar Filtros
                  </button>
                </div>

              </div>
            </section>

            <!-- Grid de microretos -->
            <div v-if="microretosFiltrados.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div v-for="reto in microretosFiltrados" :key="reto.id"
                class="bg-white rounded-[1.5rem] border border-gray-100 hover:border-[#00A859]/40 hover:shadow-[0_20px_40px_rgba(0,0,0,0.06)] shadow-sm transition-all duration-300 flex flex-col group relative overflow-hidden transform hover:-translate-y-1">

                <div class="absolute top-4 right-4 bg-[#00A859]/10 border border-[#00A859]/20 text-[#00A859] px-3 py-1 text-[9px] font-black uppercase tracking-widest rounded-full z-10">
                  {{ reto.nivel_grupo || 'Nivel ND' }}
                </div>

                <div class="p-7 flex-1 flex flex-col pt-10">
                  <h3 class="text-[#1F2937] font-black text-xl leading-tight mb-4 group-hover:text-[#00A859] transition-colors line-clamp-2" :title="reto.titulo">
                    {{ reto.titulo }}
                  </h3>
                  <div class="flex flex-col gap-2 mb-5 border-l-2 border-gray-100 group-hover:border-[#00A859]/30 pl-3 transition-colors">
                    <p class="text-[#1F2937] text-xs font-bold uppercase tracking-wider flex items-center gap-2">
                      <svg class="w-4 h-4 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                      </svg>
                      {{ reto.empresa_nombre }}
                    </p>
                    <p class="text-gray-500 text-[10px] font-bold uppercase tracking-wider flex items-center gap-2">
                      <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                      </svg>
                      {{ reto.centro_educativo || 'Centro ND' }}
                    </p>
                  </div>
                  <p class="text-gray-600 text-sm leading-relaxed line-clamp-3 mb-6 flex-1">
                    {{ reto.pregunta_reto }}
                  </p>
                  <div class="mt-auto">
                    <span class="inline-block bg-gray-50 text-gray-600 border border-gray-200 px-3 py-1.5 rounded-lg text-xs font-medium truncate max-w-full shadow-sm" :title="reto.ciclo">
                      {{ reto.ciclo }}
                    </span>
                  </div>
                </div>

                <button @click.prevent="abrirModalEliminar(reto)"
                  class="absolute top-4 left-4 w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center shadow-sm transition-all duration-300 hover:bg-red-50 hover:border-red-300 active:scale-95 z-10"
                  title="Eliminar microreto">
                  <svg class="w-4 h-4 text-gray-400 hover:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>

                <button @click="irADetalle(reto)"
                  class="w-full bg-gray-50 group-hover:bg-[#00A859] text-gray-500 group-hover:text-white font-black text-xs uppercase tracking-widest py-4 transition-all duration-300 border-t border-gray-100 group-hover:border-[#00A859] flex items-center justify-center gap-2">
                  Ver Ficha Técnica
                  <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                  </svg>
                </button>
              </div>
            </div>

            <div v-else class="text-center py-20 bg-white rounded-[2rem] border border-dashed border-gray-300 shadow-sm">
              <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-5 border border-gray-100 shadow-inner">
                <svg class="w-10 h-10 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h3 class="text-[#1F2937] font-black text-2xl mb-2">No hay resultados</h3>
              <p class="text-gray-500 text-sm max-w-md mx-auto">Prueba a limpiar los filtros o genera nuevos microretos en el Estudio interactivo.</p>
              <button @click="limpiarFiltros"
                class="mt-6 px-6 py-2 bg-white border border-gray-200 hover:border-[#00A859] hover:text-[#00A859] text-[#1F2937] rounded-full text-xs font-bold uppercase tracking-widest transition-colors shadow-sm">
                Restablecer Búsqueda
              </button>
            </div>

          </div>
        </Transition>

      </template>
    </div>
  </div>

  <!-- MODAL ELIMINAR -->
  <Transition name="fade">
    <div v-if="modalVisible"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm p-4"
      @click.self="cancelarEliminar">
      <div class="bg-white rounded-[2rem] shadow-2xl p-8 max-w-md w-full border border-gray-100">
        <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-5 border border-red-100">
          <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </div>
        <h3 class="text-[#1F2937] font-black text-2xl text-center mb-2">¿Eliminar microreto?</h3>
        <p class="text-gray-500 text-sm text-center mb-2 leading-relaxed">Vas a eliminar permanentemente:</p>
        <p class="text-[#1F2937] font-bold text-sm text-center mb-6 bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
          "{{ retoAEliminar?.titulo }}"
        </p>
        <p class="text-gray-400 text-xs text-center mb-8">Esta acción no se puede deshacer.</p>
        <div class="flex gap-3">
          <button @click="cancelarEliminar"
            class="flex-1 py-3.5 rounded-xl font-bold text-xs tracking-widest uppercase border border-gray-200 text-gray-600 hover:bg-gray-50 transition-all">
            Cancelar
          </button>
          <button @click="confirmarEliminar"
            class="flex-1 py-3.5 rounded-xl font-bold text-xs tracking-widest uppercase bg-red-500 hover:bg-red-600 text-white transition-all shadow-sm">
            Sí, eliminar
          </button>
        </div>
      </div>
    </div>
  </Transition>

  <LoginModal v-model="showLogin" @login-success="onLoginSuccess" />

</template>

<style scoped>
@import "tailwindcss";

::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: #F8FAFC; }
::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #00A859; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-up-enter-active { transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-up-leave-active { transition: all 0.2s ease-in; }
.slide-up-enter-from { opacity: 0; transform: translateY(20px); }
.slide-up-leave-to { opacity: 0; transform: translateY(-10px); }
</style>