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
        // Migration: create_payment_transactions_table.php
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->string('reference')->nullable();
            $table->string('payment_method');
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2)->default(0);
            $table->string('customer_name');
            $table->string('email');
            $table->string('phone');
            $table->text('product_details');
            $table->string('status')->default('pending'); // pending, paid, failed, expired
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactiontable');
    }
};
