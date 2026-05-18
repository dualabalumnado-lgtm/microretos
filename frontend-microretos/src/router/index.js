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
import PapeleraBaseDatos from '../views/PapeleraBaseDatos.vue'
import GestionUsuarios from '../views/GestionUsuarios.vue'
import { ROLE_ADMIN, ROLE_DOCENTE, ROLE_EMPRESA, ROLE_ROUTES } from '../stores/auth.js'

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
      meta: { requiresAuth: true, roles: [ROLE_ADMIN, ROLE_EMPRESA, ROLE_DOCENTE] }
    },
    {
      path: '/biblioteca',
      name: 'biblioteca',
      component: BibliotecaMicroretos,
      meta: { requiresAuth: true, roles: [ROLE_ADMIN, ROLE_DOCENTE, ROLE_EMPRESA] }
    },
    {
      path: '/biblioteca/:id',
      name: 'detalle-microreto',
      component: DetalleMicroreto,
      meta: { requiresAuth: true, roles: [ROLE_ADMIN, ROLE_DOCENTE, ROLE_EMPRESA] }
    },
    {
      path: '/base-datos',
      name: 'base-datos',
      component: BaseDatosDashboard,
      meta: { requiresAuth: true, roles: [ROLE_ADMIN] }
    },
    {
      path: '/papelera',
      name: 'papelera',
      component: PapeleraBaseDatos,
      meta: { requiresAuth: true, roles: [ROLE_ADMIN] }
    },
    {
      path: '/empresas',
      name: 'empresas',
      component: EmpresasView,
      meta: { requiresAuth: true, roles: [ROLE_ADMIN, ROLE_DOCENTE] }
    },
    {
      path: '/dashboard',
      name: 'dashboard-docente',
      component: DashboardDocente,
      meta: { requiresAuth: true, roles: [ROLE_ADMIN, ROLE_DOCENTE] }
    },
    {
      path: '/dashboard/sesiones',
      name: 'sesiones-registradas',
      component: SesionesRegistradas,
      meta: { requiresAuth: true, roles: [ROLE_ADMIN, ROLE_DOCENTE] }
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
      meta: { requiresAuth: true, roles: [ROLE_ADMIN, ROLE_DOCENTE, ROLE_EMPRESA] }
    },
    {
      path: '/startup-day/crear',
      name: 'startup-day-crear',
      component: StartupDayWizard,
      meta: { requiresAuth: true, roles: [ROLE_ADMIN, ROLE_DOCENTE, ROLE_EMPRESA] }
    },
    {
      path: '/startup-day/:uuid/editar',
      name: 'startup-day-editar',
      component: StartupDayWizard,
      meta: { requiresAuth: true, roles: [ROLE_ADMIN, ROLE_DOCENTE, ROLE_EMPRESA] }
    },
    {
      path: '/startup-day/:uuid',
      name: 'startup-day-detalle',
      component: StartupDayDetalle,
      meta: { requiresAuth: true, roles: [ROLE_ADMIN, ROLE_DOCENTE, ROLE_EMPRESA] }
    },
    {
      // Vista pública para validación por parte de la empresa
      path: '/startup/landing/:token',
      name: 'startup-day-landing',
      component: StartupDayLanding
    },
    {
      path: '/admin/usuarios',
      name: 'gestion-usuarios',
      component: GestionUsuarios,
      meta: { requiresAuth: true, roles: [ROLE_ADMIN] }
    }
  ]
})

// Duración del token en ms — debe coincidir con TOKEN_DURATION_MINUTES en auth.js
const TOKEN_DURATION_MS = 1440 * 60 * 1000

// Guard global: verifica autenticación y permisos de rol.
// Si no hay sesión → redirige a / con ?redirect=<ruta>
// Si hay sesión pero el rol no tiene acceso → redirige a /
router.beforeEach((to, _from, next) => {
  if (!to.meta.requiresAuth) {
    next()
    return
  }

  const token     = localStorage.getItem('admin_token')
  const createdAt = Number(localStorage.getItem('admin_token_created_at') || 0)
  const isValid   = token && createdAt && (Date.now() - createdAt) < TOKEN_DURATION_MS

  if (!isValid) {
    localStorage.removeItem('admin_token')
    localStorage.removeItem('admin_token_created_at')
    localStorage.removeItem('user_role')
    localStorage.removeItem('user_name')
    next({ path: '/', query: { redirect: to.fullPath } })
    return
  }

  // Verificar permiso de rol para esta ruta
  const role         = Number(localStorage.getItem('user_role') || ROLE_ADMIN)
  const allowedRoles = to.meta.roles ?? []
  if (allowedRoles.length > 0 && !allowedRoles.includes(role)) {
    // Redirigir al home de cada rol sin mensaje de error (silent redirect)
    next({ path: '/' })
    return
  }

  next()
})

export default router
