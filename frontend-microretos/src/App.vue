<template>
  <div class="min-h-screen bg-gray-100 p-10 flex flex-col items-center">
    <h1 class="text-3xl font-bold text-blue-600 mb-6">Generador SaaS (Vue + Laravel API)</h1>
    
    <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
      <h2 class="text-xl font-semibold mb-4">Familias Profesionales:</h2>
      
      <ul v-if="familias.length > 0" class="list-disc pl-5 space-y-2">
        <li v-for="familia in familias" :key="familia" class="text-gray-700">
          {{ familia }}
        </li>
      </ul>
      <p v-else class="text-gray-500 animate-pulse">Cargando datos desde Laravel...</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const familias = ref([])

onMounted(async () => {
  try {
    // Aquí llamamos a Backend de Laravel de forma independiente, como si fuera una API através de los
    const response = await fetch('http://localhost/generador-microretos-dualab/public/api/familias')
    familias.value = await response.json()
  } catch (error) {
    console.error('Error conectando con Laravel:', error)
  }
})
</script>