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
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('form_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->text('prompt');

            $table->enum('status', [
                'queued',
                'processing',
                'completed',
                'failed'
            ])->default('queued');

            $table->string('model')->nullable();

            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();

            $table->unsignedInteger('latency_ms')->nullable();

            $table->json('schema')->nullable();

            $table->text('error')->nullable();

            $table->timestamps();

            $table->index(['form_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
