<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import LoginModal from '../components/LoginModal.vue'; 

const router = useRouter();
const isLoaded = ref(false);
const showLogin = ref(false); // 👈 controla el modal

onMounted(() => {
  setTimeout(() => {
    isLoaded.value = true;
  }, 100);
});

// 👇 Ya no navega directamente, abre el modal
const irAGenerador = () => {
  // Si ya está logueado, navega directo
  if (localStorage.getItem('admin_token')) {
    router.push({ name: 'microretos' });
  } else {
    showLogin.value = true;
  }
};

// 👇 Solo navega si el login fue exitoso
const onLoginSuccess = () => {
  router.push({ name: 'microretos' });
};

const irABiblioteca = () => {
  router.push({ name: 'biblioteca' });
};
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] overflow-hidden font-sans text-[#1F2937] relative flex items-center justify-center p-4 md:p-12">
    
    <div class="absolute top-[-10%] left-[-10%] w-[40vw] h-[40vw] bg-[#99CC33]/10 rounded-full blur-[100px] pointer-events-none transition-opacity duration-1000" :class="isLoaded ? 'opacity-100' : 'opacity-0'"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[30vw] h-[30vw] bg-[#00A859]/10 rounded-full blur-[100px] pointer-events-none transition-opacity duration-1000 delay-300" :class="isLoaded ? 'opacity-100' : 'opacity-0'"></div>

    <div class="max-w-6xl w-full mx-auto relative z-10">
      
      <header class="flex justify-center mb-12 md:mb-20 transition-all duration-1000 ease-out transform"
        :class="isLoaded ? 'translate-y-0 opacity-100' : '-translate-y-10 opacity-0'">
  <div class="inline-flex items-center bg-[#1F2937] py-5 pr-12 pl-6 md:pl-8 rounded-[3rem] shadow-lg border border-[#333333]">
    <img src="../assets/logo.png" alt="Logo DuaLab" class="h-32 md:h-40 w-auto object-contain -mr-4 md:-mr-8 relative z-10" />
    
    <span class="font-black text-4xl md:text-6xl tracking-tighter uppercase text-white relative z-20">
      Dua<span class="text-[#00A859]">Lab</span>
    </span>
  </div>
</header>

      <main class="grid grid-cols-1 lg:grid-cols-2 gap-16 lg:gap-24 items-center">
        
        <div class="space-y-8 text-center md:text-left">
          <div class="transition-all duration-700 delay-150 ease-out transform"
               :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'">
            <div class="inline-block bg-[#99CC33]/15 text-[#00A859] px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mb-6 border border-[#99CC33]/30">
              Innovación Educativa B2B
            </div>
            <h1 class="text-5xl md:text-7xl font-black tracking-tighter leading-[1.1] text-[#121212] mb-6">
              Conecta talento <br class="hidden md:block"/> con <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#00A859] to-[#99CC33]">retos reales.</span>
            </h1>
            <p class="text-lg md:text-xl text-gray-500 leading-relaxed font-medium max-w-xl mx-auto md:mx-0">
              DuaLab es la solución definitiva para conectar empresas con el alumnado en prácticas. Transformamos necesidades empresariales en <strong class="text-[#00A859] font-bold">microretos académicos</strong> para impulsar el aprendizaje práctico y descubrir talento emergente.
            </p>
          </div>

          <div class="flex flex-col sm:flex-row items-center gap-5 pt-4 transition-all duration-700 delay-300 ease-out transform"
               :class="isLoaded ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'">
            
            <button @click="irAGenerador" class="group relative w-full sm:w-auto flex items-center justify-center gap-3 px-8 py-5 bg-gradient-to-r from-[#00A859] to-[#99CC33] text-white rounded-full font-black text-sm uppercase tracking-widest shadow-[0_10px_30px_rgba(0,168,89,0.3)] hover:shadow-[0_15px_40px_rgba(153,204,51,0.4)] transition-all duration-300 hover:-translate-y-1 active:scale-95">
              <svg class="w-5 h-5 transition-transform group-hover:rotate-180 duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
              Generador de Microretos
            </button>

            <button @click="irABiblioteca" class="w-full sm:w-auto flex items-center justify-center gap-3 px-8 py-5 bg-white text-[#1F2937] border-2 border-gray-200 rounded-full font-black text-sm uppercase tracking-widest shadow-sm hover:border-[#00A859] hover:text-[#00A859] transition-all duration-300 hover:-translate-y-1 active:scale-95">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
              Biblioteca de Retos
            </button>
          </div>
        </div>

        <div class="relative transition-all duration-1000 delay-500 ease-out transform"
             :class="isLoaded ? 'translate-y-0 opacity-100 scale-100' : 'translate-y-16 opacity-0 scale-95'">
          
          <div class="bg-white p-8 rounded-[2.5rem] shadow-[0_20px_50px_rgb(0,0,0,0.05)] border border-gray-100 relative z-10">
            <div class="flex justify-between items-center border-b border-gray-100 pb-6 mb-6">
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full bg-[#99CC33]"></div>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Flujo de Trabajo</span>
              </div>
              <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
            </div>

            <div class="space-y-6">
              <div class="flex gap-4 items-start p-4 rounded-2xl hover:bg-gray-50 transition-colors">
                <div class="bg-[#1F2937] text-white w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-black shadow-md">1</div>
                <div>
                  <h4 class="font-bold text-[#1F2937] text-lg">La Empresa propone</h4>
                  <p class="text-sm text-gray-500 leading-relaxed mt-1">Identifican una fricción o cuello de botella real en su día a día.</p>
                </div>
              </div>
              
              <div class="w-0.5 h-6 bg-gradient-to-b from-gray-200 to-[#99CC33] ml-9 -my-4 relative z-0"></div>

              <div class="flex gap-4 items-start p-4 rounded-2xl bg-[#99CC33]/10 border border-[#99CC33]/20 relative z-10">
                <div class="bg-[#99CC33] text-[#121212] w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-black shadow-md">2</div>
                <div>
                  <h4 class="font-bold text-[#00A859] text-lg">La IA lo transforma</h4>
                  <p class="text-sm text-gray-600 leading-relaxed mt-1">DuaLab genera un microreto académico alineado al currículo oficial.</p>
                </div>
              </div>

              <div class="w-0.5 h-6 bg-gradient-to-b from-[#99CC33] to-[#00A859] ml-9 -my-4 relative z-0"></div>

              <div class="flex gap-4 items-start p-4 rounded-2xl hover:bg-gray-50 transition-colors">
                <div class="bg-[#00A859] text-white w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-black shadow-md shadow-[#00A859]/30">3</div>
                <div>
                  <h4 class="font-bold text-[#1F2937] text-lg">El Alumnado resuelve</h4>
                  <p class="text-sm text-gray-500 leading-relaxed mt-1">Ganan experiencia práctica mientras aportan valor real a la empresa.</p>
                </div>
              </div>
            </div>
          </div>
          
          <div class="absolute -bottom-2 -right-2 sm:-bottom-4 sm:-right-4 z-20 bg-[#1F2937] text-white p-5 rounded-3xl shadow-2xl flex items-center gap-4 animate-bounce" style="animation-duration: 3s;">
            <div class="bg-[#99CC33] text-[#121212] rounded-full p-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
              <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Resultado</p>
              <p class="font-black whitespace-nowrap">Match Perfecto</p>
            </div>
          </div>

        </div>
      </main>
    </div>
    <!-- Al final del template, antes del último </div> -->
    <LoginModal v-model="showLogin" @login-success="onLoginSuccess" />
  </div>
</template>

<style scoped>
/* Las fuentes asumen que tienes importada la familia globalmente, si no, te recomiendo importar un sans como Inter o Montserrat */
</style>