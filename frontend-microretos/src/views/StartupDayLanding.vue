<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api.js';

const route = useRoute();
const proyecto = ref(null);
const cargando = ref(true);
const error    = ref(false);
const enviado  = ref(false);
const enviando = ref(false);
const isLoaded = ref(false);

const respuestas = ref({
  reto_comprensible:   '',
  objetivos_alineados: '',
  equipo_adecuado:     '',
  viabilidad:          '',
});
const comentarios = ref('');

onMounted(async () => {
  setTimeout(() => { isLoaded.value = true; }, 80);
  try {
    const res = await api.get(`/startup/landing/${route.params.token}`);
    proyecto.value = res.data;
  } catch {
    error.value = true;
  } finally {
    cargando.value = false;
  }
});

async function enviarValidacion() {
  enviando.value = true;
  try {
    await api.post(`/startup/landing/${route.params.token}/validar`, {
      respuestas: respuestas.value,
      comentarios: comentarios.value,
    });
    enviado.value = true;
  } finally {
    enviando.value = false;
  }
}

const preguntas = [
  { key: 'reto_comprensible',   label: '¿El planteamiento del reto es comprensible y realista?' },
  { key: 'objetivos_alineados', label: '¿Los objetivos del microproyecto se alinean con las necesidades de la empresa?' },
  { key: 'equipo_adecuado',     label: '¿El perfil del equipo de alumnos os parece adecuado para este reto?' },
  { key: 'viabilidad',          label: '¿Consideráis que el proyecto es viable en el contexto de vuestra empresa?' },
];
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] font-sans text-[#1F2937] flex flex-col">

    <!-- Fondo decorativo -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[700px] h-[400px]
                bg-[#99CC33] opacity-5 blur-[120px] rounded-full pointer-events-none z-0" />

    <!-- Header -->
    <header class="relative z-10 border-b border-gray-100 bg-white/80 backdrop-blur px-6 py-4 flex items-center gap-3 shadow-sm">
      <div class="w-9 h-9 rounded-xl bg-[#00A859]/10 border border-[#00A859]/20 flex items-center justify-center shrink-0">
        <svg class="w-5 h-5 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 2L2 7l10 5 10-5-10-5zm0 10l-10-5m10 5l10-5m-10 5v10"/>
        </svg>
      </div>
      <div>
        <p class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">DuaLab</p>
        <p class="text-xs text-gray-400">Portal de validación empresa</p>
      </div>
    </header>

    <div class="relative z-10 flex-1 flex items-start justify-center px-4 py-10">
      <div class="w-full max-w-2xl"
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
        <div v-else-if="error"
             class="text-center py-24 bg-white rounded-[2rem] border border-dashed border-gray-200 shadow-sm">
          <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-red-100">
            <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <p class="text-gray-500 text-sm font-medium">El enlace no es válido o ha expirado.</p>
        </div>

        <!-- Confirmación enviada -->
        <div v-else-if="enviado"
             class="text-center py-16 bg-white rounded-[2rem] border border-gray-100 shadow-sm">
          <div class="w-20 h-20 rounded-full bg-[#00A859]/10 border border-[#00A859]/20 flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-[#00A859]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <h2 class="text-xl font-black text-[#121212] mb-2">Validación enviada</h2>
          <p class="text-gray-400 text-sm max-w-sm mx-auto">
            Gracias por revisar el microproyecto. El equipo docente recibirá vuestro feedback.
          </p>
        </div>

        <!-- Contenido -->
        <template v-else-if="proyecto">

          <!-- Badge StartUp Day -->
          <div class="inline-flex items-center gap-2 mb-5 px-3 py-1 rounded-full
                      bg-[#00A859]/10 border border-[#00A859]/20">
            <span class="w-2 h-2 rounded-full bg-[#00A859]" />
            <span class="text-[10px] font-black uppercase tracking-widest text-[#00A859]">StartUp Day · Microproyecto</span>
          </div>

          <!-- Ya validado -->
          <div v-if="proyecto.empresa_validado"
               class="bg-[#00A859]/5 border border-[#00A859]/20 rounded-2xl p-5 mb-6 flex items-start gap-3">
            <svg class="w-5 h-5 text-[#00A859] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-sm font-bold text-[#00A859]">Este microproyecto ya ha sido validado. Gracias por vuestra participación.</p>
          </div>

          <!-- Ficha del proyecto -->
          <div class="bg-white border border-gray-100 rounded-[1.5rem] shadow-sm p-6 mb-5">
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-1">Microproyecto</p>
            <h1 class="text-xl font-black text-[#121212] mb-5">{{ proyecto.titulo }}</h1>

            <div v-if="proyecto.diseno_reto?.descripcion" class="mb-5">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-2">El reto</p>
              <p v-if="proyecto.diseno_reto.pregunta_reto"
                 class="text-sm font-bold text-[#00A859] italic mb-2">
                "{{ proyecto.diseno_reto.pregunta_reto }}"
              </p>
              <p class="text-sm text-gray-600 leading-relaxed">{{ proyecto.diseno_reto.descripcion }}</p>
            </div>

            <div v-if="proyecto.objetivos?.lista?.length" class="mb-5">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-2">Objetivos</p>
              <ul class="space-y-1.5">
                <li v-for="obj in proyecto.objetivos.lista" :key="obj"
                    class="text-sm text-gray-600 flex items-start gap-2">
                  <span class="text-[#00A859] mt-0.5 shrink-0 font-bold">›</span>{{ obj }}
                </li>
              </ul>
            </div>

            <div v-if="proyecto.equipo?.alumnos?.length">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 mb-2">Equipo</p>
              <div class="flex flex-wrap gap-2">
                <span v-for="a in proyecto.equipo.alumnos" :key="a.nombre"
                      class="text-xs bg-gray-50 border border-gray-200 px-3 py-1 rounded-full text-gray-600">
                  {{ a.nombre }}<span v-if="a.rol" class="text-gray-400"> · {{ a.rol }}</span>
                </span>
              </div>
            </div>
          </div>

          <!-- Formulario validación (solo si no está ya validado) -->
          <form v-if="!proyecto.empresa_validado" @submit.prevent="enviarValidacion"
                class="bg-white border border-gray-100 rounded-[1.5rem] shadow-sm p-6 space-y-6">

            <div class="pb-2 border-b border-gray-100">
              <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[#00A859] mb-1">Validación empresa</p>
              <p class="text-sm text-gray-400">Por favor, responded las siguientes preguntas sobre el microproyecto.</p>
            </div>

            <div v-for="preg in preguntas" :key="preg.key" class="space-y-2.5">
              <label class="text-sm text-[#1F2937] font-medium block">{{ preg.label }}</label>
              <div class="flex flex-wrap gap-3">
                <label v-for="op in ['Sí', 'No', 'Parcialmente']" :key="op"
                       :class="[
                         'flex items-center gap-2 cursor-pointer px-4 py-2 rounded-full border text-sm font-medium transition-all',
                         respuestas[preg.key] === op
                           ? 'bg-[#00A859]/10 border-[#00A859]/30 text-[#00A859]'
                           : 'bg-gray-50 border-gray-200 text-gray-500 hover:border-[#00A859]/30 hover:text-[#00A859]'
                       ]">
                  <input type="radio" :name="preg.key" :value="op" v-model="respuestas[preg.key]"
                         class="sr-only" required />
                  {{ op }}
                </label>
              </div>
            </div>

            <div>
              <label class="text-sm text-[#1F2937] font-medium block mb-2">
                Comentarios adicionales <span class="text-gray-400 font-normal">(opcional)</span>
              </label>
              <textarea
                v-model="comentarios" rows="4" maxlength="2000"
                placeholder="Sugerencias, dudas o cualquier observación..."
                class="w-full bg-white border border-gray-200 rounded-2xl px-4 py-3 text-sm
                       text-[#1F2937] placeholder-gray-400 shadow-sm
                       focus:outline-none focus:border-[#00A859] transition-colors resize-none"
              />
            </div>

            <button type="submit" :disabled="enviando"
                    class="w-full py-3 bg-[#00A859] rounded-full text-xs font-black uppercase tracking-widest
                           text-white shadow-sm hover:bg-[#00A859]/90
                           hover:shadow-[0_0_0_3px_rgba(0,168,89,0.2)]
                           transition-all active:scale-[0.99] disabled:opacity-50 disabled:cursor-not-allowed">
              {{ enviando ? 'Enviando...' : 'Enviar validación' }}
            </button>
          </form>

        </template>
      </div>
    </div>
  </div>
</template>
