import { ref, computed } from 'vue'

const DB_SECURITY_MINUTES = 30
const STORAGE_KEY = 'db_security_verified_at'

// Estado singleton compartido entre todas las instancias del composable
const _modalVisible = ref(false)
const _verifiedAt   = ref(Number(sessionStorage.getItem(STORAGE_KEY) || 0))
const _now          = ref(Date.now())
let   _resolve      = null

setInterval(() => { _now.value = Date.now() }, 60_000)

export function useDbSecurity() {
  const minutosRestantes = computed(() => {
    if (!_verifiedAt.value) return 0
    return Math.max(0, Math.floor(DB_SECURITY_MINUTES - (_now.value - _verifiedAt.value) / 60_000))
  })

  const isValid = computed(() =>
    !!_verifiedAt.value && (_now.value - _verifiedAt.value) / 60_000 < DB_SECURITY_MINUTES
  )

  async function requireDbSecurity() {
    const stored = Number(sessionStorage.getItem(STORAGE_KEY) || 0)
    if (stored && (Date.now() - stored) / 60_000 < DB_SECURITY_MINUTES) {
      if (_verifiedAt.value !== stored) _verifiedAt.value = stored
      return true
    }
    return new Promise(resolve => {
      _resolve = resolve
      _modalVisible.value = true
    })
  }

  function onVerified() {
    const now = Date.now()
    sessionStorage.setItem(STORAGE_KEY, String(now))
    _verifiedAt.value   = now
    _modalVisible.value = false
    _resolve?.(true)
    _resolve = null
  }

  function onCancelled() {
    _modalVisible.value = false
    _resolve?.(false)
    _resolve = null
  }

  return {
    modalVisible: _modalVisible,
    minutosRestantes,
    isValid,
    requireDbSecurity,
    onVerified,
    onCancelled,
  }
}
