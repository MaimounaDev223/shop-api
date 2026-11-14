<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with(['variants' => fn ($q) => $q->where('is_active', true)->orderBy('price')])
            ->when($request->query('category'), function ($q, $slug) {
                $q->whereHas('category', function ($c) use ($slug) {
                    $c->where('slug', $slug)
                        ->orWhereHas('parent', fn ($p) => $p->where('slug', $slug));
                });
            })
            ->when($request->query('search'), fn ($q, $s) => $q->where('name', 'ilike', "%{$s}%"))
            ->orderBy('name')
            ->paginate(20);

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category.parent',
            'variants' => fn ($q) => $q->where('is_active', true)->orderBy('price'),
        ]);

        return response()->json($product);
    }
}