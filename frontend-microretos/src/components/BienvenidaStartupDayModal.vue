<script setup>
defineProps({ show: Boolean })
const emit = defineEmits(['seleccionar'])
</script>

<template>
  <Transition name="bsd-fade">
    <div v-if="show"
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="relative bg-[#1a2332] border border-white/10 rounded-[2rem]
                  shadow-2xl max-w-md w-full p-8 text-white">

        <!-- Cabecera -->
        <div class="flex items-center gap-3 mb-2">
          <div class="w-12 h-12 rounded-2xl bg-amber-400/15 border border-amber-400/30
                      flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2L2 7l10 5 10-5-10-5z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 17l10 5 10-5"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12l10 5 10-5"/>
            </svg>
          </div>
          <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-amber-400 mb-0.5">Startup Day · Fase 2</p>
            <h2 class="text-xl font-black tracking-tight text-white">¿Qué necesitas?</h2>
          </div>
        </div>
        <p class="text-xs text-white/40 leading-relaxed mb-6 pl-[3.75rem]">
          Aquí se trabajan los microretos para convertirlos en microproyectos reales de empresa.
        </p>

        <!-- Opciones -->
        <div class="space-y-3 mb-6">

          <!-- a) Crear microproyecto -->
          <button @click="emit('seleccionar', 'crear')"
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
          <button @click="emit('seleccionar', 'trabajar')"
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

          <!-- c) Vista general + guía -->
          <button @click="emit('seleccionar', 'guia')"
                  class="w-full flex items-start gap-4 p-4 rounded-2xl border border-white/10
                         bg-white/5 hover:bg-blue-500/10 hover:border-blue-500/30
                         transition-all duration-200 text-left group">
            <div class="w-9 h-9 rounded-xl bg-blue-500/15 border border-blue-500/25
                        flex items-center justify-center shrink-0 mt-0.5
                        group-hover:bg-blue-500/25 transition-colors">
              <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <p class="font-black text-white text-sm mb-0.5">Vista general <span class="text-blue-400 font-normal text-xs ml-1">+ guía</span></p>
              <p class="text-xs text-white/50 leading-relaxed">Explora la vista con una guía paso a paso.</p>
            </div>
          </button>

        </div>

        <button @click="emit('seleccionar', 'trabajar')"
                class="w-full text-center text-[10px] font-bold text-white
                       hover:text-white/60 transition-colors py-1">
          Saltar
        </button>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.bsd-fade-enter-active, .bsd-fade-leave-active { transition: opacity 200ms ease; }
.bsd-fade-enter-from, .bsd-fade-leave-to { opacity: 0; }
</style>
