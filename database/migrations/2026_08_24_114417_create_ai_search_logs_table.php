<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_search_logs', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->json('tool_arguments')->nullable(); // select/filters/limit the AI decided on
            $table->json('columns')->nullable();
            $table->json('rows')->nullable();
            $table->integer('result_count')->nullable();
            $table->text('summary')->nullable(); // the AI's final natural-language answer
            $table->enum('status', ['success', 'error'])->default('success');
            $table->text('error')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_search_logs');
    }
};
