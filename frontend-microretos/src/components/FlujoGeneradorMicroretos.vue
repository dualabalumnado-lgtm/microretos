<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios'; 

// --- ESTADOS (Data) ---
const familias = ref([]);
const ciclos = ref([]);
const modulos = ref([]);
const ras = ref([]);

const seleccion = ref({
  familia: '',
  cicloId: '',
  moduloId: '',
  rasSeleccionados: [], // Guardamos el objeto RA entero para saber cuál está abierto
  ceSeleccionados: [],  // Guardamos las descripciones de los CE elegidos
  empresaSector: '',
  empresaNecesidad: '',
  entregable: ''
});

const microretoGenerado = ref(null);
const cargando = ref(false);
const guardadoExitoso = ref(false);

// --- LÓGICA DE CARGA INICIAL Y CACHÉ ---
onMounted(async () => {
  const res = await axios.get('/api/familias');
  familias.value = res.data; // Corregido: .value en lugar de .ref

  // Recuperar reto temporal si existe al recargar la página
  const cache = localStorage.getItem('microretoTemporal');
  if (cache) {
    microretoGenerado.value = JSON.parse(cache);
  }
});

// Auto-guardado temporal cuando la IA responde
watch(microretoGenerado, (nuevoReto) => {
  if (nuevoReto) {
    localStorage.setItem('microretoTemporal', JSON.stringify(nuevoReto));
    guardadoExitoso.value = false; // Reiniciamos el botón si es un reto nuevo
  }
});

// --- LÓGICA DE CARGA EN CASCADA ---
watch(() => seleccion.value.familia, async (nuevaFamilia) => {
  if (!nuevaFamilia) return;
  ciclos.value = []; 
  const res = await axios.get(`/api/familias/${encodeURIComponent(nuevaFamilia)}/ciclos`);
  ciclos.value = res.data;
});

watch(() => seleccion.value.cicloId, async (nuevoId) => {
  if (!nuevoId) return;
  modulos.value = [];
  const res = await axios.get(`/api/ciclos/${nuevoId}/modulos`);
  modulos.value = res.data;
});

watch(() => seleccion.value.moduloId, async (nuevoId) => {
  if (!nuevoId) return;
  const res = await axios.get(`/api/modulos/${nuevoId}/ra-ce`);
  ras.value = res.data;
});

// --- ACCIÓN: Generar IA ---
const generarReto = async () => {
  cargando.value = true;
  try {
    const res = await axios.post('/api/generar-microreto', {
      sector: seleccion.value.empresaSector,
      necesidad: seleccion.value.empresaNecesidad,
      ciclo_nombre: ciclos.value.find(c => c.id === seleccion.value.cicloId)?.nombre,
      modulo_nombre: modulos.value.find(m => m.id === seleccion.value.moduloId)?.nombre,
      resultados_aprendizaje: seleccion.value.rasSeleccionados.map(ra => ra.descripcion),
      criterios_evaluacion: seleccion.value.ceSeleccionados, // Enviamos los CE al backend
      entregable: seleccion.value.entregable
    });
    microretoGenerado.value = res.data;
  } catch (error) {
    console.error("Error generando reto", error);
  } finally {
    cargando.value = false;
  }
};

// --- ACCIÓN: Guardar en BD Definitiva ---
const guardarEnBD = async () => {
  try {
    await axios.post('/api/guardar-microreto-bd', {
      ...microretoGenerado.value,
      ce_evaluados: seleccion.value.ceSeleccionados,
      ciclo: ciclos.value.find(c => c.id === seleccion.value.cicloId)?.nombre,
      modulo: modulos.value.find(m => m.id === seleccion.value.moduloId)?.nombre
    });
    guardadoExitoso.value = true;
    localStorage.removeItem('microretoTemporal'); // Opcional: limpiar caché al guardar
  } catch (error) {
    console.error("Error guardando en BD", error);
    alert("Error al guardar en la Base de Datos");
  }
};
</script>

<template>
  <div class="p-8 max-w-4xl mx-auto space-y-6 bg-white rounded-xl shadow">
    <h1 class="text-2xl font-bold text-gray-800">Generador de Micro-retos con IA</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-b pb-6">
      <div>
        <label class="block font-medium">Familia Profesional</label>
        <select v-model="seleccion.familia" class="w-full border p-2 rounded">
          <option value="">Selecciona familia...</option>
          <option v-for="f in familias" :key="f" :value="f">{{ f }}</option>
        </select>
      </div>

      <div>
        <label class="block font-medium">Ciclo Formativo</label>
        <select v-model="seleccion.cicloId" :disabled="!ciclos.length" class="w-full border p-2 rounded">
          <option value="">Selecciona ciclo...</option>
          <option v-for="c in ciclos" :key="c.id" :value="c.id">{{ c.nombre }}</option>
        </select>
      </div>
    </div>

    <div class="space-y-4 border-b pb-6">
      <h2 class="text-lg font-semibold">Datos de la Empresa</h2>
      <input v-model="seleccion.empresaSector" placeholder="Sector (ej: Hostelería)" class="w-full border p-2 rounded" />
      <textarea v-model="seleccion.empresaNecesidad" placeholder="¿Qué necesidad real tienen?" class="w-full border p-2 rounded h-24"></textarea>
    </div>

    <div v-if="ras.length" class="space-y-4 pb-6">
      <h2 class="text-lg font-semibold">Resultados y Criterios a evaluar</h2>
      
      <div v-for="ra in ras" :key="ra.id" class="border rounded-lg bg-gray-50 p-4">
        <div class="flex items-start space-x-2">
          <input type="checkbox" :value="ra" v-model="seleccion.rasSeleccionados" class="mt-1" />
          <span class="text-sm font-bold text-gray-800">{{ ra.descripcion }}</span>
        </div>

        <div v-if="seleccion.rasSeleccionados.some(r => r.id === ra.id)" class="mt-3 pl-6 space-y-2 border-l-2 border-blue-300">
          <p class="text-xs font-semibold text-gray-500 uppercase">Criterios de evaluación:</p>
          <div v-for="ce in ra.criteriosEvaluacion" :key="ce.id" class="flex items-start space-x-2">
            <input type="checkbox" :value="ce.descripcion" v-model="seleccion.ceSeleccionados" class="mt-1" />
            <span class="text-sm text-gray-600">{{ ce.descripcion }}</span>
          </div>
        </div>
      </div>
    </div>

    <button 
      @click="generarReto" 
      :disabled="cargando"
      class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 disabled:bg-gray-400">
      {{ cargando ? 'Consultando a la IA...' : 'Generar Micro-reto' }}
    </button>

    <div v-if="microretoGenerado" class="mt-8 relative">
      <div class="absolute -top-4 right-4">
        <button 
          @click="guardarEnBD"
          :disabled="guardadoExitoso"
          :class="guardadoExitoso ? 'bg-green-500' : 'bg-yellow-500 hover:bg-yellow-600'"
          class="text-white px-4 py-1 rounded-full shadow-md text-sm font-bold transition-colors">
          {{ guardadoExitoso ? '✓ Guardado en Biblioteca' : 'Guardar microreto en Base Datos' }}
        </button>
      </div>

      <div class="p-6 bg-green-50 border-2 border-green-200 rounded-xl">
        <h3 class="text-xl font-bold text-green-800">{{ microretoGenerado.titulo }}</h3>
        <p class="mt-2"><strong>Contexto:</strong> {{ microretoGenerado.contexto_empresa }}</p>
        <div class="mt-4 p-4 bg-white rounded shadow-sm">
          <p class="font-bold">El Reto Técnico:</p>
          <p>{{ microretoGenerado.reto_tecnico }}</p>
        </div>
        </div>
    </div>
  </div>
</template>