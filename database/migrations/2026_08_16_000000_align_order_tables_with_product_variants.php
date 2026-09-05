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
        if (! Schema::hasColumn('orders', 'idempotency_key')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('idempotency_key', 64)
                    ->nullable()
                    ->unique()
                    ->after('number')
                    ->comment('幂等鍵');
            });
        }

        if (Schema::hasColumn('orders', 'is_paid')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('is_paid');
            });
        }

        if (Schema::hasColumn('order_details', 'product_id') && ! Schema::hasColumn('order_details', 'product_variant_id')) {
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
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration normalizes legacy schemas to the current order model shape.
    }
};
