<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\Equipment;
use App\Models\User;
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