<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\Equipment;
use App\Models\Invoice;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /** Daftar jam (buka sampai tutup) beserta status tersedia atau tidak. */
    public function availability(Court $court, string $date): array
    {
        $open = (int) config('booking.open_hour');
        $close = (int) config('booking.close_hour');
        $isToday = Carbon::parse($date)->isToday();

        $booked = BookingSlot::where('court_id', $court->id)
            ->where('booking_date', $date)
            ->pluck('slot_hour')
            ->map(fn ($h) => (int) $h)
            ->all();

        $slots = [];
        for ($h = $open; $h < $close; $h++) {
            $past = $isToday && $h <= now()->hour;

            $slots[] = [
                'hour' => $h,
                'label' => sprintf('%02d:00', $h),
                'available' => ! $past && ! in_array($h, $booked, true),
            ];
        }

        return $slots;
    }

    /** Membuat booking dengan status pending. Semua harga dihitung di sini, bukan dari frontend. */
    public function create(User $user, array $data): Booking
    {
        $court = Court::where('is_active', true)->findOrFail($data['court_id']);
        $date = $data['booking_date'];
        $start = (int) $data['start_hour'];
        $duration = (int) $data['duration_hours'];
        $hours = range($start, $start + $duration - 1);

        // Gabungkan alat yang sama jika dikirim dobel
        $requested = collect($data['equipment'] ?? [])
            ->groupBy('equipment_id')
            ->map(fn ($rows) => (int) $rows->sum('quantity'));

        try {
            return DB::transaction(function () use ($user, $court, $date, $start, $duration, $hours, $requested) {
                // Cek cepat agar pesan error jelas
                $taken = BookingSlot::where('court_id', $court->id)
                    ->where('booking_date', $date)
                    ->whereIn('slot_hour', $hours)
                    ->exists();

                if ($taken) {
                    $this->slotTaken();
                }

                // Kunci baris alat agar stok tidak dijual dua kali
                $equipmentTotal = 0;
                $items = [];

                if ($requested->isNotEmpty()) {
                    $equipment = Equipment::whereIn('id', $requested->keys())
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');

                    foreach ($requested as $id => $qty) {
                        $item = $equipment->get($id);

                        if (! $item) {
                            abort(422, 'Alat yang dipilih tidak tersedia.');
                        }

                        $left = $item->stock - $this->reserved($item->id, $date, $hours);

                        if ($qty > $left) {
                            abort(422, "Stok {$item->name} tidak cukup (tersisa ".max($left, 0).').');
                        }

                        $equipmentTotal += $item->price * $qty;
                        $items[] = [
                            'equipment_id' => $item->id,
                            'quantity' => $qty,
                            'unit_price' => $item->price,
                        ];
                    }
                }

                $booking = Booking::create([
                    'user_id' => $user->id,
                    'court_id' => $court->id,
                    'booking_date' => $date,
                    'start_time' => sprintf('%02d:00:00', $start),
                    'duration_hours' => $duration,
                    'status' => 'pending',
                    'total_price' => $court->price_per_hour * $duration + $equipmentTotal,
                ]);

                // Satu baris per jam. Unique index (court_id, booking_date, slot_hour)
                // adalah penjaga terakhir jika dua request masuk bersamaan.
                foreach ($hours as $hour) {
                    BookingSlot::create([
                        'booking_id' => $booking->id,
                        'court_id' => $court->id,
                        'booking_date' => $date,
                        'slot_hour' => $hour,
                    ]);
                }

                if ($items) {
                    $booking->items()->createMany($items);
                }

                return $booking->load(['court:id,name,floor_type', 'items.equipment:id,name']);
            });
        } catch (QueryException $e) {
            // 1062 = duplicate entry: slot direbut request lain di detik yang sama
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                $this->slotTaken();
            }

            throw $e;
        }
    }

    /** Admin menyetujui booking: invoice terbit dan member dinotifikasi. */
    public function approve(Booking $booking, User $admin): Booking
    {
        return DB::transaction(function () use ($booking, $admin) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                abort(409, 'Booking ini sudah diproses sebelumnya.');
            }

            $locked->update([
                'status' => 'approved',
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            $invoice = Invoice::create([
                'booking_id' => $locked->id,
                'invoice_number' => sprintf('INV-%s-%05d', now()->format('Ymd'), $locked->id),
                'amount' => $locked->total_price,
                'status' => 'unpaid',
                'method' => 'simulasi',
            ]);

            $locked->load('court:id,name');

            $this->notify(
                $locked->user_id,
                'Booking disetujui',
                "Booking {$locked->court->name} tanggal {$locked->booking_date->format('d-m-Y')} "
                .'pukul '.substr($locked->start_time, 0, 5)." ({$locked->duration_hours} jam) disetujui. "
                ."Silakan selesaikan pembayaran untuk invoice {$invoice->invoice_number}."
            );

            return $locked->load(['user:id,name,email', 'items.equipment:id,name', 'invoice']);
        });
    }

    /** Admin menolak booking: jam dilepas dan member dinotifikasi beserta alasannya. */
    public function reject(Booking $booking, User $admin, string $reason): Booking
    {
        return DB::transaction(function () use ($booking, $admin, $reason) {
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                abort(409, 'Booking ini sudah diproses sebelumnya.');
            }

            $locked->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);

            // Melepas jam agar bisa dipesan orang lain
            BookingSlot::where('booking_id', $locked->id)->delete();

            $locked->load('court:id,name');

            $this->notify(
                $locked->user_id,
                'Booking ditolak',
                "Booking {$locked->court->name} tanggal {$locked->booking_date->format('d-m-Y')} "
                .'pukul '.substr($locked->start_time, 0, 5)." ditolak. Alasan: {$reason}"
            );

            return $locked->load(['user:id,name,email', 'items.equipment:id,name']);
        });
    }

    public function notify(int $userId, string $title, string $message): void
    {
        UserNotification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
        ]);
    }

    /** Jumlah alat yang sudah terpakai booking aktif di jam yang beririsan. */
    private function reserved(int $equipmentId, string $date, array $hours): int
    {
        return (int) DB::table('booking_equipment as be')
            ->join('bookings as b', 'b.id', '=', 'be.booking_id')
            ->where('be.equipment_id', $equipmentId)
            ->where('b.booking_date', $date)
            ->whereIn('b.status', ['pending', 'approved'])
            ->whereExists(function ($q) use ($hours) {
                $q->select(DB::raw(1))
                    ->from('booking_slots as bs')
                    ->whereColumn('bs.booking_id', 'b.id')
                    ->whereIn('bs.slot_hour', $hours);
            })
            ->sum('be.quantity');
    }

    private function slotTaken(): never
    {
        abort(409, 'Maaf, jam yang Anda pilih sudah dipesan orang lain.');
    }
}