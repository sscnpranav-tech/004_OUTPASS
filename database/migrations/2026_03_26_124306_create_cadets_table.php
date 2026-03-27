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
        Schema::create('cadets', function (Blueprint $table) {
            $table->id();
            $table->string('rollno')->unique();
            $table->string('name');
            $table->string('class');
            $table->string('house');
            $table->enum('gender', ['male', 'female'])->default('male'); // Add the 'gender' column (gender)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cadets');
    }
};
