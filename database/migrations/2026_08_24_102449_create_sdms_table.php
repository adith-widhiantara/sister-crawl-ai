<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master list of SDM (pegawai), synced from /referensi/sdm. Upserted by id_sdm
        // so re-syncing never creates duplicates.
        Schema::create('sdms', function (Blueprint $table) {
            $table->id();
            $table->uuid('id_sdm')->unique();
            $table->string('nama_sdm')->nullable();
            $table->string('nidn')->nullable();
            $table->string('nip')->nullable();
            $table->string('nuptk')->nullable();
            $table->string('nama_status_aktif')->nullable();
            $table->string('nama_status_pegawai')->nullable();
            $table->string('jenis_sdm')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdms');
    }
};
