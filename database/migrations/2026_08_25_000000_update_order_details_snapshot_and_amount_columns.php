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
        Schema::table('order_details', function (Blueprint $table) {
            $table->string('product_sku', 64)->nullable()->after('product_name')->comment('商品 SKU');
            $table->string('product_color', 10)->nullable()->after('product_sku')->comment('商品顏色');
            $table->unsignedTinyInteger('product_size')->nullable()->after('product_color')->comment('商品尺寸');
            $table->unsignedInteger('product_price')->change();
            $table->unsignedSmallInteger('quantity')->change();
            $table->unsignedInteger('subtotal')->change();
        });

        DB::table('order_details')
            ->join('product_variants', 'order_details.product_variant_id', '=', 'product_variants.id')
            ->join('product_specs', 'product_variants.product_spec_id', '=', 'product_specs.id')
            ->update([
                'order_details.product_sku' => DB::raw('product_variants.sku'),
                'order_details.product_color' => DB::raw('product_specs.color'),
                'order_details.product_size' => DB::raw('product_specs.size'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['product_sku', 'product_color', 'product_size']);
            $table->unsignedMediumInteger('product_price')->change();
            $table->unsignedTinyInteger('quantity')->change();
            $table->unsignedMediumInteger('subtotal')->change();
        });
    }
};
