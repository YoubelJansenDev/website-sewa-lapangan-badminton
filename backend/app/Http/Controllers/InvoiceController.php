<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::with('booking.court:id,name')
            ->whereHas('booking', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(10);

        return response()->json($invoices);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $this->ensureOwner($request, $invoice);

        return response()->json([
            'invoice' => $invoice->load(['booking.court:id,name,floor_type', 'booking.items.equipment:id,name']),
        ]);
    }

    /** Pembayaran fiktif: cukup mengubah status menjadi paid. */
    public function pay(Request $request, Invoice $invoice, BookingService $service): JsonResponse
    {
        $this->ensureOwner($request, $invoice);

        DB::transaction(function () use ($invoice, $service) {
            $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'unpaid') {
                abort(409, 'Invoice ini sudah dibayar atau tidak berlaku.');
            }

            $locked->update(['status' => 'paid', 'paid_at' => now()]);

            $service->notify(
                $locked->booking->user_id,
                'Pembayaran berhasil',
                "Pembayaran invoice {$locked->invoice_number} (simulasi) berhasil. Sampai jumpa di lapangan!"
            );
        });

        return response()->json([
            'message' => 'Pembayaran berhasil (simulasi).',
            'invoice' => $invoice->fresh()->load('booking.court:id,name'),
        ]);
    }

    // 404 (bukan 403) agar ID invoice orang lain tidak bisa ditebak
    private function ensureOwner(Request $request, Invoice $invoice): void
    {
        $invoice->loadMissing('booking');

        abort_unless($invoice->booking->user_id === $request->user()->id, 404);
    }
}