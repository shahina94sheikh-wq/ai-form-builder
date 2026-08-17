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
        Schema::create('form_imports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('filename');

            $table->string('disk')->default('local');

            $table->enum('type', [
                'docx',
                'xlsx'
            ]);

            $table->enum('status', [
                'uploaded',
                'processing',
                'preview',
                'completed',
                'failed'
            ])->default('uploaded');

            $table->json('parsed_data')->nullable();

            $table->json('schema')->nullable();

            $table->json('errors')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_imports');
    }
};
