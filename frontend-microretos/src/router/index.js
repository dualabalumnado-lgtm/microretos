import { createRouter, createWebHistory } from 'vue-router'
import GeneradorMicroretos from '../views/GeneradorMicroretos.vue'
import BibliotecaMicroretos from '../views/BibliotecaMicroretos.vue'
import Home from '../views/Home.vue'
import DetalleMicroreto from '../views/DetalleMicroreto.vue'
import BaseDatosDashboard from '../views/BaseDatosDashboard.vue'
import PublicMicroreto from '../views/PublicMicroreto.vue'
import DashboardDocente from '../views/DashboardDocente.vue'

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
      path: '/dashboard',
      name: 'dashboard-docente',
      component: DashboardDocente,
      meta: { requiresAuth: true }
    },
    {
      // Vista pública para alumnado — acceso mediante token temporal (QR)
      path: '/reto/:token',
      name: 'public-microreto',
      component: PublicMicroreto
    }
  ]
})

// Guarda de navegación global: bloquea rutas protegidas si no hay sesión activa.
// Redirige a / con el parámetro ?redirect=<ruta> para que el modal de login
// sepa a dónde mandar al usuario tras autenticarse.
router.beforeEach((to, _from, next) => {
  if (to.meta.requiresAuth && !localStorage.getItem('admin_token')) {
    next({ path: '/', query: { redirect: to.fullPath } })
    return
  }
  next()
})

export default router