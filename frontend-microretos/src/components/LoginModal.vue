<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div v-if="modelValue" class="modal-overlay" @click.self="$emit('update:modelValue', false)">
        <Transition name="modal">
          <div class="modal-card" v-if="modelValue">

            <div class="modal-header">
              <div class="logo-mark">
                <span class="logo-icon">⬡</span>
              </div>
              <h2 class="modal-title">Acceso Administrador</h2>
              <p class="modal-subtitle">Panel de control exclusivo</p>
            </div>

            <form class="modal-form" @submit.prevent="handleLogin">
              <div class="field-group" :class="{ 'has-error': errors.email }">
                <label class="field-label">Correo electrónico</label>
                <div class="field-wrapper">
                  <span class="field-icon">✉</span>
                  <input
                    v-model="form.email"
                    type="email"
                    class="field-input"
                    placeholder="admin@ejemplo.com"
                    autocomplete="email"
                    @focus="errors.email = ''"
                  />
                </div>
                <span class="field-error" v-if="errors.email">{{ errors.email }}</span>
              </div>

              <div class="field-group" :class="{ 'has-error': errors.password }">
                <label class="field-label">Contraseña</label>
                <div class="field-wrapper">
                  <span class="field-icon">⬤</span>
                  <input
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    class="field-input"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    @focus="errors.password = ''"
                  />
                  <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                    {{ showPassword ? '🙈' : '👁' }}
                  </button>
                </div>
                <span class="field-error" v-if="errors.password">{{ errors.password }}</span>
              </div>

              <Transition name="alert">
                <div class="alert-error" v-if="loginError">
                  <span>⚠</span> {{ loginError }}
                </div>
              </Transition>

              <button type="submit" class="btn-submit" :class="{ loading: isLoading }" :disabled="isLoading">
                <span class="btn-text" v-if="!isLoading">Entrar</span>
                <span class="btn-loader" v-else>
                  <span></span><span></span><span></span>
                </span>
              </button>
            </form>

            <button class="modal-close" @click="$emit('update:modelValue', false)" aria-label="Cerrar">✕</button>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, reactive } from 'vue'
import api from '../api.js'

const props = defineProps({
  modelValue: Boolean
})

const emit = defineEmits(['update:modelValue', 'login-success'])

const form = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '' })
const loginError = ref('')
const isLoading = ref(false)
const showPassword = ref(false)

function validate() {
  let valid = true
  errors.email = ''
  errors.password = ''

  if (!form.email) {
    errors.email = 'El correo es obligatorio'
    valid = false
  } else if (!/\S+@\S+\.\S+/.test(form.email)) {
    errors.email = 'Formato de correo inválido'
    valid = false
  }

  if (!form.password) {
    errors.password = 'La contraseña es obligatoria'
    valid = false
  }

  return valid
}

async function handleLogin() {
  if (!validate()) return

  isLoading.value = true
  loginError.value = ''

  try {
    const response = await api.post('/admin/login', {
      email: form.email,
      password: form.password
    })

    if (response.data.success) {
      localStorage.setItem('admin_token', response.data.token)
      emit('login-success', response.data)
      emit('update:modelValue', false)
      form.email = ''
      form.password = ''
    }
  } catch (error) {
    if (error.response?.status === 401) {
      loginError.value = 'Credenciales incorrectas. Inténtalo de nuevo.'
    } else {
      loginError.value = 'Error de conexión. Inténtalo más tarde.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Mono:wght@300;400;500&display=swap');

* { box-sizing: border-box; margin: 0; padding: 0; }

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(8, 8, 12, 0.82);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}

.modal-card {
  position: relative;
  background: #0e0e14;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  padding: 3rem 2.5rem 2.5rem;
  width: 100%;
  max-width: 420px;
  box-shadow:
    0 0 0 1px rgba(255,255,255,0.04),
    0 40px 80px rgba(0,0,0,0.6),
    0 0 60px rgba(99, 76, 255, 0.08);
}

.modal-header {
  text-align: center;
  margin-bottom: 2.5rem;
}

.logo-mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  background: linear-gradient(135deg, #634cff, #a78bfa);
  border-radius: 14px;
  margin-bottom: 1.25rem;
  box-shadow: 0 8px 24px rgba(99, 76, 255, 0.35);
}

.logo-icon {
  font-size: 1.4rem;
  color: white;
}

.modal-title {
  font-family: 'DM Serif Display', serif;
  font-size: 1.6rem;
  color: #f0f0f8;
  letter-spacing: -0.02em;
  margin-bottom: 0.4rem;
}

.modal-subtitle {
  font-family: 'DM Mono', monospace;
  font-size: 0.72rem;
  color: rgba(255,255,255,0.3);
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.field-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.field-label {
  font-family: 'DM Mono', monospace;
  font-size: 0.7rem;
  color: rgba(255,255,255,0.4);
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.field-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.field-icon {
  position: absolute;
  left: 1rem;
  font-size: 0.8rem;
  color: rgba(255,255,255,0.2);
  pointer-events: none;
}

.field-input {
  width: 100%;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 10px;
  padding: 0.85rem 2.8rem 0.85rem 2.8rem;
  color: #f0f0f8;
  font-family: 'DM Mono', monospace;
  font-size: 0.88rem;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
}

.field-input::placeholder { color: rgba(255,255,255,0.15); }

.field-input:focus {
  border-color: rgba(99, 76, 255, 0.5);
  box-shadow: 0 0 0 3px rgba(99, 76, 255, 0.1);
  background: rgba(99, 76, 255, 0.04);
}

.has-error .field-input {
  border-color: rgba(255, 80, 80, 0.5);
}

.toggle-password {
  position: absolute;
  right: 0.9rem;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 0.85rem;
  color: rgba(255,255,255,0.3);
  padding: 0.2rem;
  transition: color 0.2s;
}

.toggle-password:hover { color: rgba(255,255,255,0.6); }

.field-error {
  font-family: 'DM Mono', monospace;
  font-size: 0.68rem;
  color: #ff6060;
  padding-left: 0.2rem;
}

.alert-error {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 80, 80, 0.08);
  border: 1px solid rgba(255, 80, 80, 0.2);
  border-radius: 8px;
  padding: 0.75rem 1rem;
  font-family: 'DM Mono', monospace;
  font-size: 0.78rem;
  color: #ff8080;
}

.btn-submit {
  margin-top: 0.5rem;
  width: 100%;
  padding: 0.95rem;
  background: linear-gradient(135deg, #634cff, #8b6fff);
  border: none;
  border-radius: 10px;
  color: white;
  font-family: 'DM Mono', monospace;
  font-size: 0.88rem;
  font-weight: 500;
  letter-spacing: 0.06em;
  cursor: pointer;
  transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
  box-shadow: 0 4px 20px rgba(99, 76, 255, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 48px;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 8px 28px rgba(99, 76, 255, 0.45);
}

.btn-submit:active:not(:disabled) { transform: translateY(0); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-loader {
  display: flex;
  gap: 5px;
  align-items: center;
}

.btn-loader span {
  width: 6px;
  height: 6px;
  background: white;
  border-radius: 50%;
  animation: bounce 0.6s infinite alternate;
}

.btn-loader span:nth-child(2) { animation-delay: 0.15s; }
.btn-loader span:nth-child(3) { animation-delay: 0.3s; }

@keyframes bounce {
  from { transform: translateY(0); opacity: 0.4; }
  to   { transform: translateY(-5px); opacity: 1; }
}

.modal-close {
  position: absolute;
  top: 1.25rem;
  right: 1.25rem;
  background: rgba(255,255,255,0.06);
  border: none;
  border-radius: 8px;
  color: rgba(255,255,255,0.4);
  width: 32px;
  height: 32px;
  cursor: pointer;
  font-size: 0.75rem;
  transition: background 0.2s, color 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close:hover {
  background: rgba(255,255,255,0.1);
  color: rgba(255,255,255,0.8);
}

/* Transitions */
.overlay-enter-active, .overlay-leave-active { transition: opacity 0.3s ease; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }

.modal-enter-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from { opacity: 0; transform: scale(0.9) translateY(20px); }
.modal-leave-to   { opacity: 0; transform: scale(0.95) translateY(10px); }

.alert-enter-active, .alert-leave-active { transition: all 0.25s ease; }
.alert-enter-from, .alert-leave-to { opacity: 0; transform: translateY(-6px); }
</style>