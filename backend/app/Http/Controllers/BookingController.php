<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    private const RELATIONS = [
        'court:id,name,floor_type',
        'items.equipment:id,name',
        'invoice:id,booking_id,invoice_number,status,amount',
    ];

    public function __construct(private BookingService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with(self::RELATIONS)
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json($bookings);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        // 404 (bukan 403) agar orang lain tidak bisa menebak ID booking yang ada
        abort_unless($booking->user_id === $request->user()->id, 404);

        return response()->json(['booking' => $booking->load(self::RELATIONS)]);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->service->create($request->user(), $request->validated());

        return response()->json([
            'message' => 'Booking dibuat dan menunggu verifikasi admin.',
            'booking' => $booking,
        ], 201);
    }
}