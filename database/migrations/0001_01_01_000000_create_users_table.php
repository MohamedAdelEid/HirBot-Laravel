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
        Schema::create('users', function (Blueprint $table) {
            $table->string('Id', 255)->collation('utf8mb4_general_ci')->primary();
            $table->string('FullName', 1000);
            $table->unsignedTinyInteger('role');
            $table->boolean('EmailConfirmed')->default(false);
            $table->boolean('IsVerified')->default(false);
            $table->boolean('PhoneNumberConfirmed')->default(false);
            $table->boolean('TwoFactorEnabled')->default(false);
            $table->boolean('LockoutEnabled')->default(false);
            $table->integer('AccessFailedCount')->default(0);
            $table->timestamps();
        });

        // Schema::create('password_reset_tokens', function (Blueprint $table) {
        //     $table->string('email')->primary();
        //     $table->string('token');
        //     $table->timestamp('created_at')->nullable();
        // });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
