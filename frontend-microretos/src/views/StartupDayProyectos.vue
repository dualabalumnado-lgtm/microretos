<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api.js';

const router = useRouter();
const proyectos  = ref([]);
const cargando   = ref(true);
const busqueda   = ref('');
const filtroEstado = ref('todos');

// ─── Modal de bienvenida "¿Qué necesitas?" ───────────────────────────────────
const guiaBienvenida = ref(true)

function seleccionarOpcionBienvenida(opcion) {
  guiaBienvenida.value = false
  if (opcion === 'crear') {
    router.push({ name: 'startup-day-crear' })
  }
  // 'biblioteca' y 'trabajar' → se quedan en la vista default
}
const isLoaded   = ref(false);

onMounted(async () => {
  setTimeout(() => { isLoaded.value = true; }, 80);
  try {
    const res = await api.get('/startup/proyectos');
    proyectos.value = res.data;
  } finally {
    cargando.value = false;
  }
});

const estadoLabel = { borrador: 'Borrador', publicado: 'Publicado', archivado: 'Archivado' };
const estadoColor = {
  borrador:  'bg-amber-50 border-amber-200 text-amber-700',
  publicado: 'bg-[#00A859]/10 border-[#00A859]/20 text-[#00A859]',
  archivado: 'bg-gray-100 border-gray-200 text-gray-400',
};

const proyectosFiltrados = computed(() => {
  let lista = proyectos.value;
  if (filtroEstado.value !== 'todos') lista = lista.filter(p => p.estado === filtroEstado.value);
  if (busqueda.value.trim()) {
    const q = busqueda.value.toLowerCase();
    lista = lista.filter(p =>
      p.titulo?.toLowerCase().includes(q) ||
      p.empresa_nombre?.toLowerCase().includes(q) ||
      p.centro_nombre?.toLowerCase().includes(q)
    );
  }
  return lista;
});

async function eliminar(uuid) {
  if (!confirm('¿Eliminar este microproyecto? Esta acción no se puede deshacer.')) return;
  await api.delete(`/startup/proyectos/${uuid}`);
  proyectos.value = proyectos.value.filter(p => p.uuid !== uuid);
}
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] p-4 md:p-10 font-sans text-[#1F2937]">

    <!-- ══════════ MODAL BIENVENIDA "¿QUÉ NECESITAS?" ══════════════════════════ -->
    <Transition name="fade-modal">
      <div v-if="guiaBienvenida"
           class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="relative bg-[#1a2332] border border-white/10 rounded-[2rem]
                    shadow-2xl max-w-md w-full p-8 text-white">

          <!-- Cabecera -->
          <div class="flex items-center gap-3 mb-2">
            <div class="w-12 h-12 rounded-2xl bg-amber-400/15 border border-amber-400/30
                        flex items-center justify-center shrink-0">
              <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 17l10 5 10-5"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12l10 5 10-5"/>
              </svg>
            </div>
            <div>
              <p class="text-[10px] font-black uppercase tracking-widest text-amber-400 mb-0.5">Startup Day · Fase 2</p>
              <h2 class="text-xl font-black tracking-tight">¿Qué necesitas?</h2>
            </div>
          </div>
          <p class="text-xs text-white/40 leading-relaxed mb-6 pl-[3.75rem]">
            Aquí se trabajan los microretos para convertirlos en microproyectos reales de empresa.
          </p>

          <!-- Opciones -->
          <div class="space-y-3 mb-6">

            <!-- a) Crear microproyecto -->
            <button @click="seleccionarOpcionBienvenida('crear')"
                    class="w-full flex items-start gap-4 p-4 rounded-2xl border border-white/10
                           bg-white/5 hover:bg-[#00A859]/10 hover:border-[#00A859]/30
                           transition-all duration-200 text-left group">
              <div class="w-9 h-9 rounded-xl bg-[#00A859]/15 border border-[#00A859]/25
                          flex items-center justify-center shrink-0 mt-0.5
                          group-hover:bg-[#00A859]/25 transition-colors">
                <svg class="w-4 h-4 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
              </div>
              <div>
                <p class="font-black text-white text-sm mb-0.5">Crear un microproyecto</p>
                <p class="text-xs text-white/50 leading-relaxed">Empieza el wizard guiado para registrar un nuevo microproyecto StartUp Day.</p>
              </div>
            </button>

            <!-- b) Trabajar microproyecto ya creado -->
            <button @click="seleccionarOpcionBienvenida('trabajar')"
                    class="w-full flex items-start gap-4 p-4 rounded-2xl border border-white/10
                           bg-white/5 hover:bg-[#99CC33]/10 hover:border-[#99CC33]/30
                           transition-all duration-200 text-left group">
              <div class="w-9 h-9 rounded-xl bg-[#99CC33]/15 border border-[#99CC33]/25
                          flex items-center justify-center shrink-0 mt-0.5
                          group-hover:bg-[#99CC33]/25 transition-colors">
                <svg class="w-4 h-4 text-[#99CC33]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </div>
              <div>
                <p class="font-black text-white text-sm mb-0.5">Trabajar un microproyecto creado</p>
                <p class="text-xs text-white/50 leading-relaxed">Accede a la biblioteca y continúa editando un microproyecto existente.</p>
              </div>
            </button>

            <!-- c) Ir a biblioteca -->
            <button @click="seleccionarOpcionBienvenida('biblioteca')"
                    class="w-full flex items-start gap-4 p-4 rounded-2xl border border-white/10
                           bg-white/5 hover:bg-blue-500/10 hover:border-blue-500/30
                           transition-all duration-200 text-left group">
              <div class="w-9 h-9 rounded-xl bg-blue-500/15 border border-blue-500/25
                          flex items-center justify-center shrink-0 mt-0.5
                          group-hover:bg-blue-500/25 transition-colors">
                <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 6h16M4 10h16M4 14h8"/>
                  <circle cx="17" cy="17" r="3"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 19.5l1.5 1.5"/>
                </svg>
              </div>
              <div>
                <p class="font-black text-white text-sm mb-0.5">Ver todos los microproyectos</p>
                <p class="text-xs text-white/50 leading-relaxed">Vista general de la biblioteca con todos los microproyectos registrados.</p>
              </div>
            </button>

          </div>

          <button @click="seleccionarOpcionBienvenida('biblioteca')"
                  class="w-full text-center text-[10px] font-bold text-white/25
                         hover:text-white/50 transition-colors py-1">
            Saltar
          </button>
        </div>
      </div>
    </Transition>

    <!-- Fondo decorativo -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[700px] h-[400px]
                bg-[#99CC33] opacity-5 blur-[120px] rounded-full pointer-events-none z-0" />

    <div class="relative z-10 max-w-6xl mx-auto"
         :class="isLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-3'"
         style="transition: opacity 0.4s ease, transform 0.4s ease">

      <!-- Cabecera -->
      <header class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <div class="inline-flex items-center gap-2 mb-3 px-3 py-1 rounded-full
                      bg-amber-400/10 border border-amber-400/20">
            <span class="w-2 h-2 rounded-full bg-amber-400" />
            <span class="text-[10px] font-black uppercase tracking-widest text-amber-500">Startup Day · Fase 2</span>
          </div>
          <h1 class="text-3xl md:text-4xl font-black tracking-tight text-[#121212]">
            Micro<span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00A859] to-[#99CC33]">proyectos</span>
          </h1>
          <p class="text-gray-500 text-sm mt-1">
            Aquí se trabajan los microretos para convertirlos en microproyectos de empresa reales.
          </p>
          <!-- Botón para ver la guía de nuevo -->
          <button @click="guiaBienvenida = true"
                  class="mt-2 inline-flex items-center gap-1.5 text-[10px] font-black uppercase
                         tracking-widest text-gray-400 hover:text-[#00A859] transition-colors">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            ¿Qué puedo hacer aquí?
          </button>
        </div>
        <button
          @click="router.push({ name: 'startup-day-crear' })"
          class="inline-flex items-center gap-2 px-5 py-2.5
                 bg-[#00A859] text-white rounded-full
                 text-xs font-black uppercase tracking-widest shadow-sm
                 hover:bg-[#00A859]/90 hover:shadow-[0_0_0_3px_rgba(0,168,89,0.2)]
                 transition-all active:scale-95 shrink-0"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
          </svg>
          Nuevo microproyecto
        </button>
      </header>

      <!-- Filtros -->
      <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
          <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
               fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
          </svg>
          <input
            v-model="busqueda" type="text"
            placeholder="Buscar por título, empresa o centro..."
            class="w-full bg-white border border-gray-200 rounded-2xl pl-10 pr-4 py-3
                   text-sm text-[#1F2937] placeholder-gray-400 shadow-sm
                   focus:outline-none focus:border-[#00A859] transition-colors"
          />
        </div>
        <div class="flex gap-2">
          <button v-for="op in ['todos','borrador','publicado','archivado']" :key="op"
                  @click="filtroEstado = op"
                  :class="[
                    'px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest border transition-all',
                    filtroEstado === op
                      ? 'bg-[#1F2937] text-white border-[#1F2937] shadow-md'
                      : 'bg-white text-gray-500 border-gray-200 hover:border-[#00A859] hover:text-[#00A859]'
                  ]">
            {{ op === 'todos' ? 'Todos' : estadoLabel[op] }}
          </button>
        </div>
      </div>

      <!-- Cargando -->
      <div v-if="cargando" class="flex flex-col items-center justify-center py-32">
        <svg class="animate-spin w-12 h-12 text-[#00A859] mb-4" viewBox="0 0 24 24">
          <path fill="currentColor" d="M12 2v4a6 6 0 106 6h4a10 10 0 11-10-10z"/>
        </svg>
        <p class="text-[#00A859] font-black tracking-widest uppercase text-sm animate-pulse">Cargando...</p>
      </div>

      <!-- Vacío -->
      <div v-else-if="proyectosFiltrados.length === 0"
           class="text-center py-24 bg-white rounded-[2rem] border border-dashed border-gray-200 shadow-sm">
        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-5
                    border border-gray-100 shadow-inner">
          <svg class="w-10 h-10 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M12 2L2 7l10 5 10-5-10-5zm0 10l-10-5m10 5l10-5m-10 5v10"/>
          </svg>
        </div>
        <h3 class="text-[#1F2937] font-black text-xl mb-2">
          {{ busqueda || filtroEstado !== 'todos' ? 'Sin resultados' : 'Todavía no hay microproyectos' }}
        </h3>
        <p class="text-gray-400 text-sm mb-6">
          {{ busqueda || filtroEstado !== 'todos' ? 'Prueba con otros filtros' : 'Crea tu primer microproyecto StartUp Day' }}
        </p>
        <button v-if="!busqueda && filtroEstado === 'todos'"
                @click="router.push({ name: 'startup-day-crear' })"
                class="inline-flex items-center gap-2 px-6 py-3 bg-[#00A859] text-white rounded-full
                       text-xs font-black uppercase tracking-widest shadow-sm hover:bg-[#00A859]/90
                       transition-all active:scale-95">
          Crear el primero
        </button>
      </div>

      <!-- Grid de tarjetas -->
      <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="p in proyectosFiltrados" :key="p.uuid"
          class="group bg-white border border-gray-100 rounded-[1.5rem] shadow-sm
                 hover:shadow-lg hover:-translate-y-0.5 hover:border-gray-200
                 transition-all duration-300 cursor-pointer flex flex-col"
          @click="router.push({ name: 'startup-day-detalle', params: { uuid: p.uuid } })"
        >
          <div class="p-5 flex-1 flex flex-col gap-3">
            <!-- Estado + paso -->
            <div class="flex items-center justify-between">
              <span :class="['text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full border', estadoColor[p.estado]]">
                {{ estadoLabel[p.estado] }}
              </span>
              <span class="text-[10px] text-gray-400 font-bold">Paso {{ p.paso_actual }}/8</span>
            </div>

            <!-- Título -->
            <h3 class="font-black text-[#1F2937] text-sm leading-snug line-clamp-2
                       group-hover:text-[#00A859] transition-colors">
              {{ p.titulo }}
            </h3>

            <!-- Meta -->
            <div class="space-y-1.5 text-xs text-gray-500 mt-auto">
              <div v-if="p.empresa_nombre" class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                </svg>
                {{ p.empresa_nombre }}
              </div>
              <div v-if="p.centro_nombre" class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                </svg>
                {{ p.centro_nombre }}
              </div>
              <div v-if="p.empresa_validado" class="flex items-center gap-1.5 text-[#00A859] font-bold">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                Validado por empresa
              </div>
            </div>
          </div>

          <!-- Acciones -->
          <div class="px-5 pb-4 flex gap-2 border-t border-gray-50 pt-3" @click.stop>
            <button
              @click="router.push({ name: 'startup-day-editar', params: { uuid: p.uuid } })"
              class="flex-1 py-2 rounded-xl bg-gray-50 border border-gray-200 text-xs font-black
                     uppercase tracking-widest text-gray-500
                     hover:border-[#00A859] hover:text-[#00A859] hover:bg-[#00A859]/5
                     transition-all"
            >
              Editar
            </button>
            <button
              @click="eliminar(p.uuid)"
              class="py-2 px-3 rounded-xl bg-gray-50 border border-gray-200 text-red-400
                     hover:bg-red-50 hover:border-red-200 transition-all"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
.fade-modal-enter-active,
.fade-modal-leave-active { transition: opacity 250ms ease; }
.fade-modal-enter-from,
.fade-modal-leave-to     { opacity: 0; }
</style>
