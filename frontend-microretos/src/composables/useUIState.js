import { ref } from 'vue'

// Singleton compartido entre componentes del mismo árbol Vue
const tourActivo = ref(false)

const showWelcome  = ref(false)
const welcomeRole  = ref(null)
const welcomeName  = ref('')
let   welcomeTimer = null

function triggerWelcome(role, userName) {
  clearTimeout(welcomeTimer)
  welcomeRole.value = role
  welcomeName.value = userName?.split(' ')[0] ?? ''
  showWelcome.value = true
  welcomeTimer = setTimeout(() => { showWelcome.value = false }, 5000)
}

export function useUIState() {
  return { tourActivo, showWelcome, welcomeRole, welcomeName, triggerWelcome }
}
