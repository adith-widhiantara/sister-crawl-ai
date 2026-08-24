<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sertifikasi_profesis', function (Blueprint $table) {
            $table->id();
            $table->uuid('sister_id')->unique();
            $table->uuid('id_sdm')->index();
            $table->string('jenis_sertifikasi')->nullable();
            $table->string('bidang_studi')->nullable();
            $table->string('sk_sertifikasi')->nullable();
            $table->integer('id_lembaga_sertifikasi')->nullable();
            $table->string('nama_lembaga_sertifikasi')->nullable();
            $table->string('terhitung_mulai_tanggal')->nullable();
            $table->string('terhitung_sampai_tanggal')->nullable();
            $table->string('nomor_registrasi')->nullable();
            $table->integer('tahun_sertifikasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikasi_profesis');
    }
};
