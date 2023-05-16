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
        //
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('gender')->after('name')->default(0)->comment = "0: male 1: female"; 
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->after('password')->default('user')->comment = "user: user, admin: admin, hotel: hotel";
            $table->string('phone_number')->after('name')->nullable();
            $table->string('address')->after('phone_number')->nullable();
            $table->date('date_of_birth')->after('address')->nullable();
            $table->string('avatar')->after('date_of_birth')->nullable();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
