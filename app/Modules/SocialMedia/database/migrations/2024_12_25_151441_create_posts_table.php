<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 255)->collation('utf8mb4_general_ci');
            $table->enum('type', ['poll', 'post'])->default('post');
            $table->text('content');
            $table->enum('privacy_comments', ['public', 'friends', 'private'])->default('public');
            $table->enum('visibility', ['public', 'friends', 'private'])->default('public');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('Id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
