import { createRouter, createWebHistory } from 'vue-router'
import GeneradorMicroretos from '../views/GeneradorMicroretos.vue'
import BibliotecaMicroretos from '../views/BibliotecaMicroretos.vue'
import Home from '../views/Home.vue'
import DetalleMicroreto from '../views/DetalleMicroreto.vue'
import BaseDatosDashboard from '../views/BaseDatosDashboard.vue'
import PublicMicroreto from '../views/PublicMicroreto.vue'
import DashboardDocente from '../views/DashboardDocente.vue'
import SesionesRegistradas from '../views/SesionesRegistradas.vue'
import StartupDayProyectos from '../views/StartupDayProyectos.vue'
import StartupDayWizard from '../views/StartupDayWizard.vue'
import StartupDayDetalle from '../views/StartupDayDetalle.vue'
import StartupDayLanding from '../views/StartupDayLanding.vue'
import EmpresasView from '../views/EmpresasView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: Home
    },
    {
      path: '/microretos',
      name: 'microretos',
      component: GeneradorMicroretos,
      meta: { requiresAuth: true }
    },
    {
      path: '/biblioteca',
      name: 'biblioteca',
      component: BibliotecaMicroretos,
      meta: { requiresAuth: true }
    },
    {
      path: '/biblioteca/:id',
      name: 'detalle-microreto',
      component: DetalleMicroreto,
      meta: { requiresAuth: true }
    },
    {
      path: '/base-datos',
      name: 'base-datos',
      component: BaseDatosDashboard,
      meta: { requiresAuth: true }
    },
    {
      path: '/empresas',
      name: 'empresas',
      component: EmpresasView,
      meta: { requiresAuth: true }
    },
    {
      path: '/dashboard',
      name: 'dashboard-docente',
      component: DashboardDocente,
      meta: { requiresAuth: true }
    },
    {
      path: '/dashboard/sesiones',
      name: 'sesiones-registradas',
      component: SesionesRegistradas,
      meta: { requiresAuth: true }
    },
    {
      // Vista pública para alumnado — acceso mediante token temporal (QR)
      path: '/reto/:token',
      name: 'public-microreto',
      component: PublicMicroreto
    },
    {
      path: '/startup-day',
      name: 'startup-day',
      component: StartupDayProyectos,
      meta: { requiresAuth: true }
    },
    {
      path: '/startup-day/crear',
      name: 'startup-day-crear',
      component: StartupDayWizard,
      meta: { requiresAuth: true }
    },
    {
      path: '/startup-day/:uuid/editar',
      name: 'startup-day-editar',
      component: StartupDayWizard,
      meta: { requiresAuth: true }
    },
    {
      path: '/startup-day/:uuid',
      name: 'startup-day-detalle',
      component: StartupDayDetalle,
      meta: { requiresAuth: true }
    },
    {
      // Vista pública para validación por parte de la empresa
      path: '/startup/landing/:token',
      name: 'startup-day-landing',
      component: StartupDayLanding
    }
  ]
})

// Duración del token en ms — debe coincidir con TOKEN_DURATION_MINUTES en auth.js
// y con config/sanctum.php → expiration (1440 min = 24 h).
const TOKEN_DURATION_MS = 1440 * 60 * 1000

// Guarda de navegación global: bloquea rutas protegidas si no hay sesión activa
// o si el token presente está caducado. Redirige a / con ?redirect=<ruta> para
// que el modal de login sepa a dónde enviar al usuario tras autenticarse.
router.beforeEach((to, _from, next) => {
  if (!to.meta.requiresAuth) {
    next()
    return
  }

  const token     = localStorage.getItem('admin_token')
  const createdAt = Number(localStorage.getItem('admin_token_created_at') || 0)
  const isValid   = token && createdAt && (Date.now() - createdAt) < TOKEN_DURATION_MS

  if (!isValid) {
    // Limpia credenciales inválidas/caducadas antes de redirigir
    localStorage.removeItem('admin_token')
    localStorage.removeItem('admin_token_created_at')
    next({ path: '/', query: { redirect: to.fullPath } })
    return
  }

  next()
})

export default router