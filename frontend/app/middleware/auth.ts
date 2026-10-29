/** Halaman khusus pengguna yang sudah login (redirect tamu ke /login). */
export default defineNuxtRouteMiddleware(() => {
  const auth = useAuth()
  if (!auth.isLoggedIn.value) {
    return navigateTo('/login')
  }
})
