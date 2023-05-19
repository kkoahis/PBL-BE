<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\SoftDeletes;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking', function (Blueprint $table) {
            $table->id();
            // user_id
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            // hotel_id
            $table->foreignId('hotel_id')->constrained('hotel')->onDelete('cascade')->onUpdate('cascade');

            $table->integer('room_count');
            $table->double('total_amount');
            // $table->string('status')->default('pending');
            $table->string('description')->nullable();
            $table->boolean('is_payment')->default(false);
            $table->string('payment_type')->nullable();
            $table->dateTime('date_in');
            $table->dateTime('date_out');
            $table->dateTime('date_booking');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking');
    }
};
