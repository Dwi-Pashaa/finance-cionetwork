<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel pengaturan status channel saldo (Manual & Xendit)
        Schema::create('balance_channel_settings', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->unique(); // 'manual', 'xendit'
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Seed default channel settings
        DB::table('balance_channel_settings')->insert([
            [
                'channel' => 'manual',
                'name' => 'Saldo Manual',
                'is_active' => true,
                'description' => 'Penambahan dan penyesuaian saldo secara manual oleh Admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'channel' => 'xendit',
                'name' => 'Saldo Xendit',
                'is_active' => true,
                'description' => 'Penambahan saldo otomatis melalui Payment Gateway Xendit',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 2. Tambah kolom balance_manual dan balance_xendit pada api_client_balances
        Schema::table('api_client_balances', function (Blueprint $table) {
            $table->decimal('balance_manual', 18, 2)->default(0)->after('api_client_id');
            $table->decimal('balance_xendit', 18, 2)->default(0)->after('balance_manual');
        });

        // Migrasikan saldo yang sudah ada ke balance_manual
        DB::statement('UPDATE api_client_balances SET balance_manual = balance WHERE balance_manual = 0 AND balance > 0');

        // 3. Tambah kolom balance_type, source, dan xendit metadata pada balance_adjustments
        Schema::table('balance_adjustments', function (Blueprint $table) {
            $table->string('balance_type')->default('manual')->after('type'); // 'manual', 'xendit'
            $table->string('source')->default('manual')->after('balance_type'); // 'manual', 'xendit', 'api_deduct', 'api_refund', etc.
            $table->string('reference_id')->nullable()->after('source');
            $table->string('xendit_invoice_id')->nullable()->after('reference_id');
            $table->string('payment_status')->nullable()->after('xendit_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('balance_adjustments', function (Blueprint $table) {
            $table->dropColumn(['balance_type', 'source', 'reference_id', 'xendit_invoice_id', 'payment_status']);
        });

        Schema::table('api_client_balances', function (Blueprint $table) {
            $table->dropColumn(['balance_manual', 'balance_xendit']);
        });

        Schema::dropIfExists('balance_channel_settings');
    }
};
