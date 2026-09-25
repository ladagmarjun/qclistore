<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Units in stock per colour, e.g. {"Black": 4, "Tan": 2}. Empty for products without colours.
            $table->jsonb('color_stock')->default(new Expression("('{}')"))->after('colors');
        });

        // Start existing products off by splitting their current stock evenly across their colours.
        DB::table('products')->orderBy('id')->each(function (object $product) {
            $colors = json_decode($product->colors ?? '[]', true) ?: [];

            if ($colors === []) {
                return;
            }

            $share = intdiv((int) $product->stock, count($colors));
            $colorStock = array_fill_keys($colors, $share);
            $colorStock[$colors[0]] += (int) $product->stock - $share * count($colors);

            DB::table('products')->where('id', $product->id)->update(['color_stock' => json_encode($colorStock)]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('color_stock');
        });
    }
};
