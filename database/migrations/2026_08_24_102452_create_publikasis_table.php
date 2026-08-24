<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publikasis', function (Blueprint $table) {
            $table->id();
            $table->uuid('sister_id')->unique();
            $table->uuid('id_sdm')->index();
            $table->string('kategori_kegiatan')->nullable();
            $table->text('judul')->nullable();
            $table->integer('quartile')->nullable();
            $table->json('bidang_keilmuan')->nullable();
            $table->string('jenis_publikasi')->nullable();
            $table->string('tanggal')->nullable();
            $table->string('asal_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publikasis');
    }
};
