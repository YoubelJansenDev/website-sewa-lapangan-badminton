<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD kelola lapangan & alat sewa (admin).
 * Endpoint BARU — tidak mengubah controller/route yang sudah berjalan.
 */
class CourtEquipmentController extends Controller
{
    // ---------- Lapangan ----------

    public function courtIndex(): JsonResponse
    {
        return response()->json(['courts' => Court::orderBy('id')->get()]);
    }

    public function courtStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'floor_type' => ['required', 'string', 'max:50'],
            'price_per_hour' => ['required', 'numeric', 'min:0'],
        ]);

        $court = Court::create($data + ['is_active' => true]);

        return response()->json(['message' => 'Lapangan ditambahkan.', 'court' => $court], 201);
    }

    public function courtUpdate(Request $request, Court $court): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'floor_type' => ['required', 'string', 'max:50'],
            'price_per_hour' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $court->update($data);

        return response()->json(['message' => 'Lapangan diperbarui.', 'court' => $court]);
    }

    /**
     * Soft-deactivate: lapangan nonaktif tidak tampil di daftar member,
     * tetapi data historis booking tetap utuh.
     */
    public function courtDestroy(Court $court): JsonResponse
    {
        $court->update(['is_active' => false]);

        return response()->json(['message' => 'Lapangan dinonaktifkan.']);
    }

    // ---------- Alat sewa ----------

    public function equipmentIndex(): JsonResponse
    {
        return response()->json(['equipment' => Equipment::orderBy('id')->get()]);
    }

    public function equipmentStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
        ]);

        $equipment = Equipment::create($data + ['is_active' => true]);

        return response()->json(['message' => 'Alat ditambahkan.', 'equipment' => $equipment], 201);
    }

    public function equipmentUpdate(Request $request, Equipment $equipment): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $equipment->update($data);

        return response()->json(['message' => 'Alat diperbarui.', 'equipment' => $equipment]);
    }

    public function equipmentDestroy(Equipment $equipment): JsonResponse
    {
        $equipment->update(['is_active' => false]);

        return response()->json(['message' => 'Alat dinonaktifkan.']);
    }
}
