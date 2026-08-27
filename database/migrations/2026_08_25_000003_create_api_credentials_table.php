<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained('api_clients')->cascadeOnDelete();
            $table->string('key_id', 64)->unique();
            $table->char('secret_hash', 64);
            $table->text('secret_encrypted');
            $table->enum('status', ['active', 'revoked'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->timestamp('revoked_at')->nullable();

            $table->index(['api_client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_credentials');
    }
};
