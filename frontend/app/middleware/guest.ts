/** Halaman khusus tamu: login, register, lupa/reset password. */
export default defineNuxtRouteMiddleware(() => {
  const auth = useAuth()
  if (auth.isLoggedIn.value) {
    return navigateTo('/')
  }
})
