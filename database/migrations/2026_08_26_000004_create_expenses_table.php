<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_category_id')->constrained('finance_categories')->restrictOnDelete();
            $table->date('transaction_date');
            $table->decimal('amount', 18, 2);
            $table->boolean('has_admin_fee')->default(false);
            $table->decimal('admin_fee_amount', 18, 2)->default(0);
            $table->string('payee', 150)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('transaction_date');
            $table->index('finance_category_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
