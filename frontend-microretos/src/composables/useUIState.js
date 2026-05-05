import { ref } from 'vue'

// Singleton compartido entre componentes del mismo árbol Vue
const tourActivo = ref(false)

export function useUIState() {
  return { tourActivo }
}
