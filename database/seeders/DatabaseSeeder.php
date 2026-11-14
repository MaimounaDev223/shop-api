<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $maquillage = Category::firstOrCreate(['slug' => 'maquillage'], ['name' => 'Maquillage']);
        $cheveux = Category::firstOrCreate(['slug' => 'cheveux'], ['name' => 'Cheveux']);

        $levres = Category::firstOrCreate(['slug' => 'levres'], ['name' => 'Lèvres', 'parent_id' => $maquillage->id]);
        $shampoings = Category::firstOrCreate(['slug' => 'shampoings'], ['name' => 'Shampoings', 'parent_id' => $cheveux->id]);

        $this->product($levres, 'Rouge à lèvres mat', 'Fini mat, longue tenue.', [
            ['Rose nude', 4500, 12],
            ['Rouge cerise', 4500, 8],
            ['Prune', 4500, 0],
        ]);

        $this->product($levres, 'Gloss brillant', 'Brillance et confort.', [
            ['Transparent', 3000, 20],
            ['Rose bonbon', 3000, 15],
        ]);

        $this->product($shampoings, 'Shampoing hydratant', 'Nettoie sans dessécher.', [
            ['250 ml', 3500, 25],
            ['500 ml', 6000, 10],
        ]);
    }

    private function product(Category $category, string $name, string $description, array $variants): void
    {
        $product = Product::firstOrCreate(
            ['name' => $name],
            ['category_id' => $category->id, 'description' => $description],
        );

        if ($product->wasRecentlyCreated) {
            foreach ($variants as [$label, $price, $stock]) {
                $product->variants()->create([
                    'name' => $label,
                    'price' => $price,
                    'stock_quantity' => $stock,
                ]);
            }
        }
    }
}