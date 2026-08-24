<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crawl_items', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->index();
            $table->uuid('id_sdm');
            $table->string('nama_sdm')->nullable();
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crawl_items');
    }
};
