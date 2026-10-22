<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use Illuminate\Http\JsonResponse;

class EquipmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'equipment' => Equipment::where('is_active', true)->orderBy('id')->get(),
        ]);
    }
}