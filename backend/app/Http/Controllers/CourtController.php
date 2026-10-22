<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourtController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'courts' => Court::where('is_active', true)->orderBy('id')->get(),
        ]);
    }

    public function availability(Request $request, Court $court, BookingService $service): JsonResponse
    {
        abort_unless($court->is_active, 404);

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        return response()->json([
            'court' => $court->only('id', 'name'),
            'date' => $data['date'],
            'slots' => $service->availability($court, $data['date']),
        ]);
    }
}