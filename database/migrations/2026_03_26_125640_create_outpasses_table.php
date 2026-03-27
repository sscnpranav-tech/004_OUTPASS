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
        Schema::create('outpasses', function (Blueprint $table) {
            $table->id();
            $table->string('rollno');
            $table->string('name');
            $table->string('gender');
            $table->string('class');
            $table->string('house');
            $table->string('type');
            $table->string('from_date');
            $table->string('to_date');
            $table->string('from_time');
            $table->string('to_time');
            $table->string('reason');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outpasses');
    }
};
