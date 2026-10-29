# Rencana Kerja SmashArena

## 1. Status saat ini

### Backend (selesai dan sudah dites di Postman)
- Auth: register, login, logout, refresh, me, forgot-password, reset-password (JWT cookie, throttle).
- Member: courts, availability, equipment, bookings (buat, daftar, detail), invoices (daftar, detail, bayar simulasi), notifications (daftar, tandai dibaca, tandai semua).
- Admin: bookings (daftar dengan filter status, detail, approve, reject dengan alasan), ping.
- Database (MySQL): users, courts, equipment, bookings, booking_slots, booking_equipment, invoices, user_notifications (+ tabel bawaan Laravel).
- Seeder: AdminSeeder, CourtSeeder (3 lapangan), EquipmentSeeder (3 alat).
- Konfigurasi bisnis: backend/config/booking.php (open_hour 8, close_hour 23, max_duration 4, max_advance_days 30). Timezone Asia/Jakarta.

### Frontend (Nuxt 4)
- Sudah: project dibuat (template minimal), Tailwind v4 aktif, frontend/.env dan .env.example.
- Kode referensi fondasi (Fase F0) ada di bawah, tetapi MUNGKIN belum semua file dibuat. Audit dulu (Prompt 0).

## 2. Kontrak API (prefix /api, JSON, cookie "token", kirim Accept: application/json)

### Auth
- POST /auth/register {name, email, phone?, password, password_confirmation} -> 201 {user}
- POST /auth/login {email, password} -> 200 {user}; 401 {message}; 429 bila lebih dari 5 per menit
- GET  /auth/me -> {user}; 401 bila belum login
- POST /auth/refresh -> {message}; cookie diperbarui; 401 bila sesi habis
- POST /auth/logout -> {message}
- POST /auth/forgot-password {email} -> {message} (selalu sama)
- POST /auth/reset-password {token, email, password, password_confirmation} -> {message} atau 422
- user = {id, name, email, phone, role}

### Member (auth:api)
- GET  /courts -> {courts: [{id, name, floor_type, price_per_hour}]}
- GET  /courts/{id}/availability?date=YYYY-MM-DD -> {court, date, slots: [{hour, label, available}]} (jam 08 sampai 22)
- GET  /equipment -> {equipment: [{id, name, price, stock}]}
- POST /bookings {court_id, booking_date, start_hour, duration_hours (1-4), equipment?: [{equipment_id, quantity}]}
  -> 201 {message, booking}; 409 slot bentrok; 422 validasi atau stok alat kurang
- GET  /bookings -> paginasi {data: [booking + court + items + invoice]}
- GET  /bookings/{id} -> {booking}
- GET  /invoices -> paginasi; GET /invoices/{id} -> {invoice}; POST /invoices/{id}/pay -> {message, invoice} (409 bila sudah dibayar)
- GET  /notifications -> {unread_count, notifications: paginasi}
- POST /notifications/{id}/read ; POST /notifications/read-all

### Admin (auth:api + role:admin)
- GET  /admin/bookings?status=pending|approved|rejected|cancelled -> paginasi
- GET  /admin/bookings/{id}
- POST /admin/bookings/{id}/approve -> invoice terbit, member dinotifikasi (409 bila bukan pending)
- POST /admin/bookings/{id}/reject {reason (5-500 karakter)} -> jam dilepas, member dinotifikasi

### Status
- booking: pending -> approved | rejected | cancelled
- invoice: unpaid -> paid | expired
- Pembayaran SIMULASI (tidak ada payment gateway).

## 3. Aturan bisnis
- Jam operasional 08:00-23:00; booking harus selesai paling lambat 23:00; durasi 1-4 jam; maksimal 30 hari ke depan; tidak boleh jam yang sudah lewat.
- Booking pending dan approved menahan slot. Rejected atau cancelled melepas slot.
- Stok alat dihitung per jam yang beririsan (booking pending dan approved).

## 4. Rencana kerja (centang saat selesai)

### Frontend
- [ ] F0  Fondasi dan auth UI (1-2 hari): nuxt.config.ts, main.css, composables (useApi, useAuth), utils/parseApiError, plugin auth.client, middleware (auth, guest, admin), layouts (default, auth), FormInput, halaman index, login, register, forgot-password, reset-password, booking (sementara), admin (sementara)
- [ ] F1  Halaman booking member (3-4 hari): /booking dengan alur pilih tanggal -> lapangan -> jam (grid dari availability) -> durasi -> alat -> ringkasan harga -> kirim; tangani 409 dan 422
- [ ] F2  Riwayat, invoice, notifikasi member (3 hari): /riwayat, /riwayat/[id] (detail, invoice, tombol bayar simulasi), ikon notifikasi dengan jumlah belum dibaca dan halaman /notifikasi
- [ ] F3  Admin booking (2-3 hari): /admin/bookings dengan filter status, detail, tombol setujui, dan tolak (modal alasan)
- [ ] F4  Landing page lengkap (2 hari): hero, cara booking, pilihan lapangan, sewa alat, CTA, footer (rancangan: gradasi putih-hijau, minimalis)
- [ ] F5  Polesan (2 hari): toast, skeleton loading, empty state, halaman 404, responsif, aksesibilitas

### Backend (Tahap 3)
- [ ] B1  Member membatalkan booking: POST /bookings/{id}/cancel (hanya pending, atau approved yang invoice-nya belum dibayar); lepas slot; invoice jadi expired; notifikasi
- [ ] B2  Invoice kedaluwarsa otomatis: command artisan bookings:expire-unpaid + scheduler (batas bayar di config/booking.php); lepas slot dan notifikasi
- [ ] B3  Admin kelola lapangan dan alat: CRUD /admin/courts dan /admin/equipment (nonaktifkan, bukan hapus, bila sudah dipakai booking)
- [ ] B4  Statistik admin: GET /admin/stats (booking pending, booking hari ini, pendapatan bulan ini dari invoice paid)
- [ ] B5  Endpoint publik opsional: GET /public/courts untuk landing page (tanpa login, tanpa data sensitif)
- [ ] B6  Feature test (Pest atau PHPUnit): auth, role 403, akses data orang lain 404, anti double booking, approve dan reject, bayar, stok alat
- [ ] B7  Hardening: APP_DEBUG=false di produksi, cookie secure + SameSite, CORS produksi, JWT TTL, throttle, log

### Dokumentasi dan repo
- [ ] D1  README.md lengkap: deskripsi, fitur, stack, arsitektur, ERD (docs/ERD.md), cara instalasi backend dan frontend, env, daftar endpoint, screenshot, roadmap
- [ ] D2  Dokumentasi API (Scribe atau file OpenAPI) dan ekspor koleksi Postman ke docs/postman/
- [ ] D3  Perbarui database/schema.sql (mysqldump --no-data) setelah migrasi baru
- [ ] D4  Cek akhir: .env tidak ikut Git, .env.example lengkap, tidak ada password asli di repo

## 5. Definisi selesai tiap fase
Fitur berjalan di browser dan di Postman, test lulus, tidak ada error di Console/terminal, git status bersih dari file rahasia, commit sudah dibuat, checklist di atas dicentang.