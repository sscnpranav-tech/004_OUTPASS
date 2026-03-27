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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('from_date');
            $table->string('to_date');
            $table->string('from_time');
            $table->string('to_time');
            $table->string('reason');
            $table->string('target_mode')->default('classes');
            $table->string('allowed_classes');
            $table->text('allowed_cadets')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
