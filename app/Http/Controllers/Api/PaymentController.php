<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function pay(Request $request, Order $order, PaymentGateway $gateway): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'operator' => ['required', 'in:orange,moov'],
            'phone' => ['required', 'regex:/^(\+223)?[0-9]{8}$/'],
        ]);

        return DB::transaction(function () use ($order, $data, $gateway) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->payment_status === 'paid') {
                return response()->json(['message' => 'Cette commande est déjà payée.'], 409);
            }

            if ($order->status === 'cancelled') {
                return response()->json(['message' => 'Cette commande est annulée.'], 409);
            }

            $result = $gateway->charge($data['operator'], $data['phone'], $order->total);

            if (! $result->success) {
                $order->update([
                    'payment_status' => 'failed',
                    'payment_method' => $data['operator'],
                ]);

                return response()->json([
                    'message' => $result->message,
                    'order' => $order->load('items'),
                ], 402);
            }

            $order->update([
                'payment_status' => 'paid',
                'payment_method' => $data['operator'],
                'payment_reference' => $result->reference,
                'paid_at' => now(),
                'status' => 'confirmed',
            ]);

            return response()->json($order->load('items'));
        });
    }
}