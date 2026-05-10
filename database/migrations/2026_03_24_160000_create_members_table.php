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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50)->comment('名字');
            $table->string('last_name', 50)->comment('姓氏');
            $table->char('national_id_number', 10)->comment('身分證字號');
            $table->string('email', 100)->comment('電子郵件');
            $table->dateTime('email_verified_at')->nullable()->default(null)->comment('電子郵件驗證時間');
            $table->string('phone', 20)->comment('手機號碼');
            $table->dateTime('phone_verified_at')->nullable()->default(null)->comment('手機號碼驗證時間');
            $table->string('password', 255)->comment('密碼');
            $table->date('birth_date')->comment('生日');
            $table->text('address')->comment('住址');
            $table->unsignedTinyInteger('gender')->comment('性別');
            $table->rememberToken();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('deleted_at')->nullable();

            $table->char('active_national_id_number', 10)
                ->storedAs('IF(deleted_at IS NULL, national_id_number, NULL)');
            $table->string('active_email', 100)
                ->storedAs('IF(deleted_at IS NULL, email, NULL)');
            $table->string('active_phone', 20)
                ->storedAs('IF(deleted_at IS NULL, phone, NULL)');

            $table->unique('active_national_id_number');
            $table->unique('active_email');
            $table->unique('active_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};