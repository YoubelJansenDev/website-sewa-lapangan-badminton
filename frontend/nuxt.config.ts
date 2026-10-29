import tailwindcss from '@tailwindcss/vite'

export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  ssr: false, // mode SPA: cookie login dikirim langsung oleh browser ke Laravel
  css: ['~/assets/css/main.css'],
  vite: { plugins: [tailwindcss()] },
  runtimeConfig: {
    public: {
      // Diisi dari .env (NUXT_PUBLIC_API_BASE). Jangan ditulis di tempat lain.
      apiBase: '',
    },
  },
  app: {
    head: {
      title: 'SmashArena - Booking Lapangan Badminton',
      htmlAttrs: { lang: 'id' },
      link: [
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap',
        },
      ],
    },
  },
})