<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected,cancelled'],
        ]);

        $bookings = Booking::with(['user:id,name,email', 'court:id,name', 'items.equipment:id,name', 'invoice'])
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(15);

        return response()->json($bookings);
    }

    public function show(Booking $booking): JsonResponse
    {
        return response()->json([
            'booking' => $booking->load(['user:id,name,email,phone', 'court', 'items.equipment:id,name', 'invoice']),
        ]);
    }

    public function approve(Request $request, Booking $booking): JsonResponse
    {
        $booking = $this->service->approve($booking, $request->user());

        return response()->json(['message' => 'Booking disetujui dan invoice dibuat.', 'booking' => $booking]);
    }

    public function reject(Request $request, Booking $booking): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $booking = $this->service->reject($booking, $request->user(), $data['reason']);

        return response()->json(['message' => 'Booking ditolak.', 'booking' => $booking]);
    }
}