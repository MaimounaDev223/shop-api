<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = $request->user()->orders()->with('items')->latest()->paginate(10);

        return response()->json($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return response()->json($order->load('items'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'distinct', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
        ]);

        $order = DB::transaction(function () use ($request, $data) {
            $variants = ProductVariant::with('product')
                ->whereIn('id', collect($data['items'])->pluck('variant_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines = [];
            $total = 0;

            foreach ($data['items'] as $item) {
                $variant = $variants[$item['variant_id']];
                $quantity = $item['quantity'];

                if (! $variant->is_active || ! $variant->product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => ["« {$variant->product->name} » n'est plus disponible."],
                    ]);
                }

                if ($variant->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => ["Stock insuffisant pour « {$variant->product->name} - {$variant->name} »."],
                    ]);
                }

                $variant->decrement('stock_quantity', $quantity);

                $lines[] = [
                    'product_variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'variant_name' => $variant->name,
                    'unit_price' => $variant->price,
                    'quantity' => $quantity,
                ];

                $total += $variant->price * $quantity;
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'total' => $total,
                'delivery_address' => $data['delivery_address'] ?? null,
            ]);

            $order->items()->createMany($lines);

            return $order;
        });

        return response()->json($order->load('items'), 201);
    }
}