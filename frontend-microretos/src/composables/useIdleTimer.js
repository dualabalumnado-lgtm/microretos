import { ref, onMounted, onUnmounted } from 'vue'

/**
 * Detecta inactividad del usuario.
 * @param {number} timeoutMinutes  Minutos de inactividad antes de disparar onIdle
 * @param {number} warningMinutes  Minutos antes del timeout en los que se avisa (onWarning)
 * @param {Function} onIdle        Callback cuando se agota el tiempo
 * @param {Function} onWarning     Callback cuando entra en el periodo de aviso
 * @param {Function} onActive      Callback cuando el usuario vuelve a estar activo
 */
export function useIdleTimer({
  timeoutMinutes = 60,
  warningMinutes = 2,
  onIdle = () => {},
  onWarning = () => {},
  onActive = () => {},
} = {}) {
  const isIdle = ref(false)
  const isWarning = ref(false)
  const secondsUntilLogout = ref(0)

  const TIMEOUT_MS = timeoutMinutes * 60 * 1000
  const WARNING_MS = warningMinutes * 60 * 1000

  let idleTimer = null
  let warningTimer = null
  let countdownInterval = null

  const clearTimers = () => {
    clearTimeout(idleTimer)
    clearTimeout(warningTimer)
    clearInterval(countdownInterval)
  }

  const startCountdown = () => {
    secondsUntilLogout.value = warningMinutes * 60
    countdownInterval = setInterval(() => {
      secondsUntilLogout.value = Math.max(0, secondsUntilLogout.value - 1)
    }, 1000)
  }

  const reset = () => {
    clearTimers()

    if (isWarning.value || isIdle.value) {
      isWarning.value = false
      isIdle.value = false
      onActive()
    }

    // Programar aviso
    warningTimer = setTimeout(() => {
      isWarning.value = true
      startCountdown()
      onWarning()

      // Programar logout tras el periodo de aviso
      idleTimer = setTimeout(() => {
        isIdle.value = true
        isWarning.value = false
        clearInterval(countdownInterval)
        onIdle()
      }, WARNING_MS)
    }, TIMEOUT_MS - WARNING_MS)
  }

  const EVENTS = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click']

  onMounted(() => {
    EVENTS.forEach(e => window.addEventListener(e, reset, { passive: true }))
    reset()
  })

  onUnmounted(() => {
    EVENTS.forEach(e => window.removeEventListener(e, reset))
    clearTimers()
  })

  return { isIdle, isWarning, secondsUntilLogout, reset }
}
