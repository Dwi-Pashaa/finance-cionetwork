<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->boolean('is_manual_balance_enabled')->default(true)->after('status');
            $table->boolean('is_xendit_balance_enabled')->default(true)->after('is_manual_balance_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            $table->dropColumn(['is_manual_balance_enabled', 'is_xendit_balance_enabled']);
        });
    }
};
