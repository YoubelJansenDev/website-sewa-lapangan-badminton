# 🏸 SmashArena — Aplikasi Booking Lapangan Badminton

Aplikasi web untuk gor milik UMKM: anggota memesan lapangan & menyewa alat secara online, admin memverifikasi pesanan dan mengelola aset gor.

## Fitur

| Role | Fitur |
|---|---|
| **Member** | Register/login, lihat lapangan & slot kosong real-time, booking multi-lapangan + sewa raket/kokoh, bayar invoice (simulasi), riwayat & detail booking, notifikasi |
| **Admin** | Verifikasi booking (setujui/tolak + alasan), kelola lapangan (CRUD + nonaktifkan), kelola alat sewa (CRUD + nonaktifkan) |

## Tech Stack

- **Backend:** Laravel 11, MySQL, JWT auth (cookie httpOnly `token`)
- **Frontend:** Nuxt 3 (SPA mode), TypeScript, Tailwind CSS v4
- **Arsitektur:** REST API JSON, cookie-based auth tanpa token di JavaScript

## Struktur Proyek

```
backend/   → Laravel API (routes/api.php, app/Http/Controllers, app/Models, app/Services)
frontend/  → Nuxt SPA (app/pages, app/composables, app/layouts, app/middleware)
docs/      → PLAN.md (kontrak API & roadmap), ERD.md
```

## Cara Menjalankan

### Prasyarat
PHP ≥ 8.2 + Composer, Node.js ≥ 20 + npm, MySQL (mis. Laragon).

### 1. Backend
```bash
cd backend
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
# edit .env: DB_DATABASE, DB_USERNAME, DB_PASSWORD, ADMIN_EMAIL
php artisan migrate --seed    # membuat tabel + data contoh + akun admin
php artisan serve             # http://localhost:8000
```
> `ADMIN_EMAIL` di `.env` menjadi akun admin pertama saat seeding.

### 2. Frontend
```bash
cd frontend
npm install
cp .env.example .env          # isi NUXT_PUBLIC_API_BASE=http://localhost:8000/api
npm run dev                   # http://localhost:3000
```

## Alur Utama

1. Member memesan lapangan pada jam kosong (+ alat sewa opsional) → status **pending**
2. Admin menyetujui → invoice otomatis dibuat + member menerima notifikasi
3. Admin menolak → wajib memberi alasan, member menerima notifikasi
4. Membayar invoice (simulasi) → status **paid**

## Catatan Teknis

- Semua pemanggilan API frontend lewat satu pintu: `frontend/app/composables/useApi.ts`
- Autentikasi memakai JWT dalam cookie **httpOnly** — aman dari XSS, logout = hapus cookie
- Nonaktifkan (bukan hapus) lapangan/alat agar riwayat booking lama tetap utuh
- Rate limit login: 5x/menit (lihat `routes/api.php`)