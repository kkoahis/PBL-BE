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
        Schema::create('hotel', function (Blueprint $table) {
            $table->id();
            // create by user
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('email')->unique();
            $table->double('price')->nullable();
            $table->longText('description')->nullable();

            $table->string('address');
            $table->string('city');
            $table->string('nation');

            $table->string('hotline')->unique();
            $table->integer('room_total');
            $table->unsignedInteger('parking_slot');
            $table->integer('bathrooms');
            $table->float('rating')->nullable();

            $table->text('amenities');
            $table->text('Safety_Hygiene');
            // check-in date
            $table->date('check_in')->nullable();
            // check-out date
            $table->date('check_out')->nullable();
            // number of guests
            $table->integer('guests')->nullable();

            $table->timestamps();
            // soft deletes
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel');
    }
};
