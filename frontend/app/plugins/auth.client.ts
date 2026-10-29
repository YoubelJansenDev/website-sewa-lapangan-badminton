/**
 * Inisialisasi sesi saat aplikasi dimuat (SPA):
 * cek /auth/me lalu lindungi halaman sebelum render pertama.
 */
export default defineNuxtPlugin({
  name: 'auth-init',
  async setup(nuxtApp) {
    const auth = useAuth()
    nuxtApp.hook('app:created', async () => {
      await auth.fetchUser()
    })
  },
})
