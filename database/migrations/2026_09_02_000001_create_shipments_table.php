<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->string('provider', 32)->nullable()->comment('物流服務商');
            $table->string('tracking_number', 64)->nullable()->comment('物流追蹤編號');
            $table->unsignedTinyInteger('status')->default(1)->comment('物流狀態');
            $table->string('shipping_method', 32)->comment('配送方式');
            $table->string('recipient_name', 50)->comment('收件人姓名');
            $table->string('recipient_phone', 20)->comment('收件人電話');
            $table->text('recipient_address')->nullable()->comment('收件地址');
            $table->string('store_code', 32)->nullable()->comment('超商門市代碼');
            $table->json('request_payload')->nullable()->comment('請求內容');
            $table->json('response_payload')->nullable()->comment('回應內容');
            $table->timestamp('shipped_at')->nullable()->comment('出貨時間');
            $table->timestamp('delivered_at')->nullable()->comment('送達時間');
            $table->timestamps();
            $table->unique(['provider', 'tracking_number'], 'shipments_provider_tracking_number_unique');
            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};