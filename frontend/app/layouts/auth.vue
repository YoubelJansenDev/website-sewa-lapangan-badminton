<template>
  <!-- Layout halaman auth: kartu terpusat di atas latar hijau lembut -->
  <div class="relative grid min-h-screen place-items-center overflow-hidden bg-green-50 px-4 py-10">
    <div class="hero-blob left-[-6rem] top-[-6rem] h-56 w-56 bg-green-200" />
    <div class="hero-blob bottom-[-8rem] right-[-6rem] h-72 w-72 bg-emerald-200" />

    <div class="page-enter relative w-full max-w-md">
      <NuxtLink to="/" class="group mb-6 flex items-center justify-center gap-2">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-green-600 text-xl font-extrabold text-white transition group-hover:scale-105">S</span>
        <span class="text-2xl font-extrabold tracking-tight text-black">SmashArena</span>
      </NuxtLink>
      <div class="rounded-2xl border border-green-100 bg-white p-6 shadow-xl shadow-green-600/5 sm:p-8">
        <slot />
      </div>
      <p class="mt-6 text-center text-sm text-gray-400">&copy; {{ new Date().getFullYear() }} SmashArena</p>
    </div>
  </div>
</template>
