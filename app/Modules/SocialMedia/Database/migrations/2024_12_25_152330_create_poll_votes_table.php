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
        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 255)->collation('utf8mb4_general_ci');
            $table->foreignId('option_id')->constrained('options')->cascadeOnDelete();
            $table->foreignId('poll_id')->constrained('polls')->cascadeOnDelete();
            $table->timestamps();

            // A user can only vote once per poll
            $table->unique(['user_id', 'poll_id']);

            $table->foreign('user_id')->references('Id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
    }
};
