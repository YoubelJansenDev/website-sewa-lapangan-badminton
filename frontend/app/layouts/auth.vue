<template>
  <!-- Layout halaman auth: kartu terpusat di atas latar hijau lembut -->
  <div class="grid min-h-screen place-items-center bg-green-50 px-4 py-10">
    <div class="w-full max-w-md">
      <NuxtLink to="/" class="mb-6 flex items-center justify-center gap-2">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-green-600 text-xl font-extrabold text-white">S</span>
        <span class="text-2xl font-extrabold tracking-tight text-black">SmashArena</span>
      </NuxtLink>
      <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
        <slot />
      </div>
      <p class="mt-6 text-center text-sm text-gray-500">&copy; {{ new Date().getFullYear() }} SmashArena</p>
    </div>
  </div>
</template>
