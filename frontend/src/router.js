import { createRouter, createWebHistory } from 'vue-router'
import LoginView from './views/LoginView.vue'
import NotesView from './views/NotesView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/notes' },
    { path: '/login', component: LoginView },
    { path: '/notes', component: NotesView, meta: { requiresAuth: true } },
  ],
})

router.beforeEach((to) => {
  if (to.meta.requiresAuth && !localStorage.getItem('token')) return '/login'
})

export default router
