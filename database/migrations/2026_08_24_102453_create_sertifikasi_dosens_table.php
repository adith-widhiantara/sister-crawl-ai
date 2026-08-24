<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sertifikasi_dosens', function (Blueprint $table) {
            $table->id();
            $table->uuid('sister_id')->unique();
            $table->uuid('id_sdm')->index();
            $table->string('jenis_sertifikasi')->nullable();
            $table->string('bidang_studi')->nullable();
            $table->integer('tahun_sertifikasi')->nullable();
            $table->string('sk_sertifikasi')->nullable();
            $table->string('nomor_registrasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikasi_dosens');
    }
};
