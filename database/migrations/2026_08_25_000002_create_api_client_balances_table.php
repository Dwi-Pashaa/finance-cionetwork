<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_client_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->unique()->constrained('api_clients')->cascadeOnDelete();
            $table->decimal('balance', 18, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_client_balances');
    }
};
