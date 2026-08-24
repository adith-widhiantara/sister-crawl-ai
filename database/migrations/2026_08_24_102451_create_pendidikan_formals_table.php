<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendidikan_formals', function (Blueprint $table) {
            $table->id();
            $table->uuid('sister_id')->unique();
            $table->uuid('id_sdm')->index();
            $table->string('jenjang_pendidikan')->nullable();
            $table->string('gelar_akademik')->nullable();
            $table->string('bidang_studi')->nullable();
            $table->string('nama_perguruan_tinggi')->nullable();
            $table->integer('tahun_lulus')->nullable();
            // ponytail: docs say integer, sandbox actually returns a status label like "Ubah" sometimes.
            $table->string('jenis_ajuan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendidikan_formals');
    }
};
