<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // akses sudah dijaga middleware auth:api di route
    }

    public function rules(): array
    {
        $open = (int) config('booking.open_hour');
        $close = (int) config('booking.close_hour');
        $maxDate = now()->addDays((int) config('booking.max_advance_days'))->toDateString();

        return [
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.$maxDate],
            'start_hour' => ['required', 'integer', 'between:'.$open.','.($close - 1)],
            'duration_hours' => ['required', 'integer', 'between:1,'.config('booking.max_duration')],
            'equipment' => ['nullable', 'array', 'max:10'],
            'equipment.*.equipment_id' => ['required', 'integer', 'exists:equipment,id'],
            'equipment.*.quantity' => ['required', 'integer', 'between:1,20'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $close = (int) config('booking.close_hour');
            $start = (int) $this->input('start_hour');
            $duration = (int) $this->input('duration_hours');

            if ($start + $duration > $close) {
                $validator->errors()->add('duration_hours', 'Durasi melewati jam tutup ('.sprintf('%02d:00', $close).').');
            }

            if ($this->input('booking_date') === now()->toDateString() && $start <= now()->hour) {
                $validator->errors()->add('start_hour', 'Jam yang dipilih sudah lewat.');
            }
        });
    }
}