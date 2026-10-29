<script setup lang="ts">
// Landing page: hero + ringkasan layanan (minimalist-modern).
useHead({ title: 'SmashArena — Booking Lapangan Badminton' })
const auth = useAuth()
</script>

<template>
  <div class="page-enter">
    <!-- Hero -->
    <section class="relative overflow-hidden rounded-3xl bg-green-600 px-6 py-16 text-center text-white sm:px-12 sm:py-24">
      <div class="hero-blob left-[-4rem] top-[-4rem] h-48 w-48 bg-white" />
      <div class="hero-blob bottom-[-6rem] right-[-4rem] h-64 w-64 bg-green-300" />
      <div class="hero-blob right-[20%] top-[-3rem] h-32 w-32 bg-emerald-200" />

      <div class="relative">
        <span class="inline-block rounded-full border border-white/25 bg-white/10 px-4 py-1.5 text-xs font-semibold tracking-wide backdrop-blur-sm">
          🏸 Gor milik UMKM lokal
        </span>
        <h1 class="mt-6 text-3xl font-extrabold tracking-tight sm:text-5xl">
          Pesan Lapangan Badminton, <br class="hidden sm:block">Lebih Mudah.
        </h1>
        <p class="mx-auto mt-4 max-w-xl text-sm text-green-100 sm:text-base">
          Lihat jam kosong secara real-time, pesan beberapa lapangan sekaligus,
          sewa raket dan kokoh dalam satu transaksi.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
          <NuxtLink
            to="/booking"
            class="rounded-xl bg-white px-6 py-3 text-sm font-bold text-green-700 shadow-lg shadow-black/10 hover:-translate-y-0.5 hover:bg-green-50 hover:shadow-xl hover:shadow-black/15"
          >Pesan Lapangan Sekarang</NuxtLink>
          <NuxtLink
            v-if="!auth.isLoggedIn.value"
            to="/register"
            class="rounded-xl border border-white/40 px-6 py-3 text-sm font-bold text-white backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/10"
          >Daftar Gratis</NuxtLink>
        </div>
      </div>
    </section>

    <!-- Ringkasan layanan -->
    <section class="mt-12 grid gap-4 sm:grid-cols-3">
      <div class="card-hover rounded-2xl border border-gray-200 bg-white p-6">
        <span class="grid h-11 w-11 place-items-center rounded-xl bg-green-50 text-2xl">🏸</span>
        <h2 class="mt-3 font-bold">Lapangan Berkualitas</h2>
        <p class="mt-1 text-sm text-gray-500">Lapangan standar profesional dengan pencahayaan terang.</p>
      </div>
      <div class="card-hover rounded-2xl border border-gray-200 bg-white p-6">
        <span class="grid h-11 w-11 place-items-center rounded-xl bg-green-50 text-2xl">🕒</span>
        <h2 class="mt-3 font-bold">Real-time Availability</h2>
        <p class="mt-1 text-sm text-gray-500">Jam kosong selalu diperbarui — tanpa double booking.</p>
      </div>
      <div class="card-hover rounded-2xl border border-gray-200 bg-white p-6">
        <span class="grid h-11 w-11 place-items-center rounded-xl bg-green-50 text-2xl">🧾</span>
        <h2 class="mt-3 font-bold">Tagihan Transparan</h2>
        <p class="mt-1 text-sm text-gray-500">Rincian biaya lapangan dan sewa alat jelas di invoice.</p>
      </div>
    </section>
  </div>
</template>
