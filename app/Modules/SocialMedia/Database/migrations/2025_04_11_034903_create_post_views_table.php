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
        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 255)->collation('utf8mb4_general_ci');
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->timestamp('last_viewed_at');
            $table->timestamps();

            $table->foreign('user_id')->references('Id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'post_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_views');
    }
};
