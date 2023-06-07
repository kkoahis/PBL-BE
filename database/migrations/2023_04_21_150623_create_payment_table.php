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
        Schema::create('payment', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('booking_id')->constrained('booking')->onDelete('cascade')->onUpdate('cascade')->unique();
            // QR
            $table->longText('qr_code')->nullable();
            $table->longText('qr_code_url')->nullable();
            // isPayment
            $table->boolean('payment_status')->default(false);
            $table->double('total_amount');
            // discound
            $table->double('discount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment');
    }
};
