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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');
            $table->string('provider', 32)->comment('金流服務商');
            $table->string('provider_transaction_id', 128)->nullable()->comment('金流服務商交易編號');
            $table->string('merchant_trade_no', 64)->nullable()->unique()->comment('商店交易編號');
            $table->unsignedInteger('amount')->comment('交易金額');
            $table->char('currency', 3)->default('TWD')->comment('幣別');
            $table->unsignedTinyInteger('status')->default(1)->comment('交易狀態');
            $table->unsignedTinyInteger('payment_method')->nullable()->comment('付款方式');
            $table->json('request_payload')->nullable()->comment('請求內容');
            $table->json('response_payload')->nullable()->comment('回應內容');
            $table->timestamp('paid_at')->nullable()->comment('付款時間');
            $table->timestamp('failed_at')->nullable()->comment('付款失敗時間');
            $table->timestamp('refunded_at')->nullable()->comment('退款時間');
            $table->timestamps();
            $table->unique(['provider', 'provider_transaction_id'], 'payment_transactions_provider_transaction_unique');
            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};