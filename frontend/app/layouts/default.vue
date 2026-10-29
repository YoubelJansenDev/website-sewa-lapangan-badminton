<script setup lang="ts">
// Layout utama: navbar untuk semua halaman non-auth.
const auth = useAuth()
const router = useRouter()

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50/60 text-black">
    <header class="sticky top-0 z-40 border-b border-gray-200/70 bg-white/80 backdrop-blur-md">
      <nav class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
        <NuxtLink to="/" class="group flex items-center gap-2">
          <span class="grid h-8 w-8 place-items-center rounded-lg bg-green-600 text-lg font-extrabold text-white transition group-hover:scale-105">S</span>
          <span class="text-lg font-extrabold tracking-tight">SmashArena</span>
        </NuxtLink>

        <div class="flex items-center gap-1 sm:gap-2">
          <template v-if="auth.isLoggedIn.value">
            <NuxtLink to="/booking" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-black">Booking</NuxtLink>
            <NuxtLink to="/riwayat" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-black">Riwayat</NuxtLink>
            <NuxtLink to="/notifikasi" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-black">Notifikasi</NuxtLink>
            <NuxtLink
              v-if="auth.isAdmin.value"
              to="/admin"
              class="rounded-lg px-3 py-2 text-sm font-medium text-green-700 hover:bg-green-50"
            >Admin</NuxtLink>
            <NuxtLink
              v-if="auth.isAdmin.value"
              to="/admin/kelola"
              class="rounded-lg px-3 py-2 text-sm font-medium text-green-700 hover:bg-green-50"
            >Kelola</NuxtLink>
            <button
              type="button"
              class="ml-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:border-gray-300 hover:bg-gray-100"
              @click="handleLogout"
            >Keluar</button>
          </template>
          <template v-else>
            <NuxtLink to="/login" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-black">Masuk</NuxtLink>
            <NuxtLink
              to="/register"
              class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-green-600/20 hover:bg-green-700 hover:shadow-md hover:shadow-green-600/25"
            >Daftar</NuxtLink>
          </template>
        </div>
      </nav>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
      <slot />
    </main>

    <footer class="border-t border-gray-200/70 bg-white py-6 text-center text-sm text-gray-400">
      &copy; {{ new Date().getFullYear() }} SmashArena — Booking Lapangan Badminton
    </footer>
  </div>
</template>
