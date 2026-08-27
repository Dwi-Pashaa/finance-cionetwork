<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nonce_cache', function (Blueprint $table) {
            $table->id();
            $table->string('client_id', 64);
            $table->string('nonce', 128);
            $table->timestamp('expires_at');

            $table->unique(['client_id', 'nonce']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nonce_cache');
    }
};
