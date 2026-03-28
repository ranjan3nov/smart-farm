<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('moisture')->nullable();
            $table->unsignedSmallInteger('rain')->nullable();
            $table->float('temp')->nullable();
            $table->float('humidity')->nullable();
            $table->float('water_dist')->nullable();
            $table->enum('tank_status', ['OK', 'EMPTY'])->nullable();
            $table->enum('pump_command', ['ON', 'OFF'])->default('OFF');
            $table->text('ai_reason')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        //
    }
};
