<?php

use Illuminate\Database\Migrations\Migration;
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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('product_spec_id')->constrained('product_specs');
            $table->string('sku', 64)->unique();
            $table->unsignedMediumInteger('price')->comment('價格');
            $table->unsignedInteger('stock_quantity')->default(0)->comment('庫存數量');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['product_id', 'product_spec_id']);
        });

        DB::table('products')
            ->orderBy('id')
            ->each(function (object $product): void {
                DB::table('product_variants')->insert([
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'product_spec_id' => $product->product_spec_id,
                    'sku' => sprintf('LEGACY-%08d', $product->id),
                    'price' => $product->price,
                    'stock_quantity' => 0,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                ]);
            });

        Schema::table('order_details', function (Blueprint $table) {
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants');
        });

        DB::table('order_details')
            ->orderBy('id')
            ->each(function (object $orderDetail): void {
                DB::table('order_details')
                    ->where('id', $orderDetail->id)
                    ->update(['product_variant_id' => $orderDetail->product_id]);
            });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            $table->unsignedBigInteger('product_variant_id')->nullable(false)->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_spec_id']);
            $table->dropColumn(['product_spec_id', 'price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_spec_id')
                ->nullable()
                ->after('id')
                ->constrained('product_specs');
            $table->unsignedMediumInteger('price')->nullable()->after('name')->comment('價格');
        });

        DB::table('product_variants')
            ->orderBy('id')
            ->each(function (object $variant): void {
                DB::table('products')
                    ->where('id', $variant->product_id)
                    ->whereNull('product_spec_id')
                    ->update([
                        'product_spec_id' => $variant->product_spec_id,
                        'price' => $variant->price,
                    ]);
            });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('product_spec_id')->nullable(false)->change();
            $table->unsignedMediumInteger('price')->nullable(false)->change();
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->nullable()
                ->after('order_id')
                ->constrained('products');
        });

        DB::table('order_details')
            ->orderBy('id')
            ->each(function (object $orderDetail): void {
                $productId = DB::table('product_variants')
                    ->where('id', $orderDetail->product_variant_id)
                    ->value('product_id');

                DB::table('order_details')
                    ->where('id', $orderDetail->id)
                    ->update(['product_id' => $productId]);
            });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });

        Schema::dropIfExists('product_variants');
    }
};