<?php

use App\Enums\PaymentTransaction\Provider as PaymentTransactionProvider;
use App\Enums\Shipment\Provider as ShipmentProvider;
use App\Enums\Shipment\ShippingMethod;
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
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropUnique('payment_transactions_provider_transaction_unique');
            $table->unsignedTinyInteger('provider_code')->nullable()->after('provider')->comment('金流服務商');
        });

        DB::table('payment_transactions')->update([
            'provider_code' => DB::raw(sprintf(
                "CASE provider WHEN 'ecpay' THEN %d WHEN 'newebpay' THEN %d ELSE %d END",
                PaymentTransactionProvider::ECPAY->value,
                PaymentTransactionProvider::NEWEBPAY->value,
                PaymentTransactionProvider::ECPAY->value,
            )),
        ]);

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn('provider');
            $table->renameColumn('provider_code', 'provider');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->unsignedTinyInteger('provider')->nullable(false)->comment('金流服務商')->change();
            $table->unique(['provider', 'provider_transaction_id'], 'payment_transactions_provider_transaction_unique');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropUnique('shipments_provider_tracking_number_unique');
            $table->unsignedTinyInteger('provider_code')->nullable()->after('provider')->comment('物流服務商');
            $table->unsignedTinyInteger('shipping_method_code')->nullable()->after('shipping_method')->comment('配送方式');
        });

        DB::table('shipments')->update([
            'provider_code' => DB::raw(sprintf(
                "CASE provider WHEN 'ecpay_logistics' THEN %d WHEN 'tcat' THEN %d WHEN 'post' THEN %d ELSE NULL END",
                ShipmentProvider::ECPAY_LOGISTICS->value,
                ShipmentProvider::TCAT->value,
                ShipmentProvider::POST->value,
            )),
            'shipping_method_code' => DB::raw(sprintf(
                "CASE shipping_method WHEN 'home_delivery' THEN %d WHEN 'convenience_store' THEN %d ELSE %d END",
                ShippingMethod::HOME_DELIVERY->value,
                ShippingMethod::CONVENIENCE_STORE->value,
                ShippingMethod::HOME_DELIVERY->value,
            )),
        ]);

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['provider', 'shipping_method']);
            $table->renameColumn('provider_code', 'provider');
            $table->renameColumn('shipping_method_code', 'shipping_method');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->unsignedTinyInteger('provider')->nullable()->comment('物流服務商')->change();
            $table->unsignedTinyInteger('shipping_method')->nullable(false)->comment('配送方式')->change();
            $table->unique(['provider', 'tracking_number'], 'shipments_provider_tracking_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropUnique('payment_transactions_provider_transaction_unique');
            $table->string('provider_name', 32)->nullable()->after('provider')->comment('金流服務商');
        });

        DB::table('payment_transactions')->update([
            'provider_name' => DB::raw(sprintf(
                "CASE provider WHEN %d THEN 'ecpay' WHEN %d THEN 'newebpay' ELSE 'ecpay' END",
                PaymentTransactionProvider::ECPAY->value,
                PaymentTransactionProvider::NEWEBPAY->value,
            )),
        ]);

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn('provider');
            $table->renameColumn('provider_name', 'provider');
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('provider', 32)->nullable(false)->comment('金流服務商')->change();
            $table->unique(['provider', 'provider_transaction_id'], 'payment_transactions_provider_transaction_unique');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropUnique('shipments_provider_tracking_number_unique');
            $table->string('provider_name', 32)->nullable()->after('provider')->comment('物流服務商');
            $table->string('shipping_method_name', 32)->nullable()->after('shipping_method')->comment('配送方式');
        });

        DB::table('shipments')->update([
            'provider_name' => DB::raw(sprintf(
                "CASE provider WHEN %d THEN 'ecpay_logistics' WHEN %d THEN 'tcat' WHEN %d THEN 'post' ELSE NULL END",
                ShipmentProvider::ECPAY_LOGISTICS->value,
                ShipmentProvider::TCAT->value,
                ShipmentProvider::POST->value,
            )),
            'shipping_method_name' => DB::raw(sprintf(
                "CASE shipping_method WHEN %d THEN 'home_delivery' WHEN %d THEN 'convenience_store' ELSE 'home_delivery' END",
                ShippingMethod::HOME_DELIVERY->value,
                ShippingMethod::CONVENIENCE_STORE->value,
            )),
        ]);

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['provider', 'shipping_method']);
            $table->renameColumn('provider_name', 'provider');
            $table->renameColumn('shipping_method_name', 'shipping_method');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->string('provider', 32)->nullable()->comment('物流服務商')->change();
            $table->string('shipping_method', 32)->nullable(false)->comment('配送方式')->change();
            $table->unique(['provider', 'tracking_number'], 'shipments_provider_tracking_number_unique');
        });
    }
};
