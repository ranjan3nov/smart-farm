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
            $table->unsignedSmallInteger('moisture');
            $table->unsignedSmallInteger('rain');
            $table->float('temp');
            $table->float('humidity');
            $table->float('water_dist');
            $table->enum('tank_status', ['OK', 'EMPTY']);
            $table->enum('pump_command', ['ON', 'OFF'])->default('OFF');
            $table->text('ai_reason')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void {}
};
