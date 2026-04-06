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
        Schema::create('ai_test_runs', function (Blueprint $table) {
            $table->id();
            $table->string('scenario_key');
            $table->string('scenario_title');
            $table->json('payload');
            $table->text('response_body')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->boolean('ok')->default(false);
            $table->timestamps();

            $table->index(['scenario_key', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_test_runs');
    }
};
