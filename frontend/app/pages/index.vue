<script setup lang="ts">
// Landing sementara (versi lengkap dikerjakan di fase tersendiri).
useHead({ title: 'SmashArena — Booking Lapangan Badminton' })
const auth = useAuth()
</script>

<template>
  <div>
    <!-- Hero -->
    <section class="rounded-3xl bg-green-600 px-6 py-16 text-center text-white sm:px-12 sm:py-24">
      <h1 class="text-3xl font-extrabold tracking-tight sm:text-5xl">
        Pesan Lapangan Badminton, <br class="hidden sm:block">Lebih Mudah.
      </h1>
      <p class="mx-auto mt-4 max-w-xl text-sm text-green-100 sm:text-base">
        Lihat jam kosong secara real-time, pesan beberapa lapangan sekaligus,
        sewa raket dan kokoh dalam satu transaksi.
      </p>
      <div class="mt-8 flex flex-wrap justify-center gap-3">
        <NuxtLink
          to="/booking"
          class="rounded-xl bg-white px-6 py-3 text-sm font-bold text-green-700 transition hover:bg-green-50"
        >Pesan Lapangan Sekarang</NuxtLink>
        <NuxtLink
          v-if="!auth.isLoggedIn.value"
          to="/register"
          class="rounded-xl border border-white/40 px-6 py-3 text-sm font-bold text-white transition hover:bg-white/10"
        >Daftar Gratis</NuxtLink>
      </div>
    </section>

    <!-- Ringkasan layanan -->
    <section class="mt-12 grid gap-4 sm:grid-cols-3">
      <div class="rounded-2xl border border-gray-200 p-6">
        <p class="text-2xl">🏸</p>
        <h2 class="mt-2 font-bold">Lapangan Berkualitas</h2>
        <p class="mt-1 text-sm text-gray-500">Lapangan standar profesional dengan pencahayaan terang.</p>
      </div>
      <div class="rounded-2xl border border-gray-200 p-6">
        <p class="text-2xl">🕒</p>
        <h2 class="mt-2 font-bold">Real-time Availability</h2>
        <p class="mt-1 text-sm text-gray-500">Jam kosong selalu diperbarui — tanpa double booking.</p>
      </div>
      <div class="rounded-2xl border border-gray-200 p-6">
        <p class="text-2xl">🧾</p>
        <h2 class="mt-2 font-bold">Tagihan Transparan</h2>
        <p class="mt-1 text-sm text-gray-500">Rincian biaya lapangan dan sewa alat jelas di invoice.</p>
      </div>
    </section>
  </div>
</template>
