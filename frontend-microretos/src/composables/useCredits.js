import { ref } from 'vue'

const abierto = ref(false)

export function useCredits() {
  return {
    creditosAbierto: abierto,
    abrirCreditos: () => (abierto.value = true),
    cerrarCreditos: () => (abierto.value = false),
  }
}
