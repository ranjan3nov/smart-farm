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
        Schema::create('farm_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Smart Farm');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('tank_height_cm')->default(20);
            $table->unsignedTinyInteger('moisture_threshold')->default(30);
            $table->unsignedTinyInteger('moisture_max')->default(70);
            $table->unsignedTinyInteger('ai_decision_interval_minutes')->default(5);
            $table->unsignedSmallInteger('send_interval_seconds')->default(300);
            $table->string('ai_driver')->default('http');
            $table->string('ai_endpoint')->nullable();
            $table->text('ai_api_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_settings');
    }
};
