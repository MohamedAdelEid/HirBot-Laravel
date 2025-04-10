<?php

use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionStatusEnum;
use App\Modules\SocialMedia\Domain\Enums\Connection\ConnectionTypeEnum;
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
        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->string('requester_id', 255)->collation('utf8mb4_general_ci');
            $table->string('receiver_id', 255)->collation('utf8mb4_general_ci');
            $table->enum('status', ConnectionStatusEnum::values())->default('pending');
            $table->enum('type', ConnectionTypeEnum::values())->default('connection');
            $table->timestamps();

            $table->foreign('requester_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade');

            // Ensure a user can't send multiple connection requests to the same user
            $table->unique(['requester_id', 'receiver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connections');
    }
};
