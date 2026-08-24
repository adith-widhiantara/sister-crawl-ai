<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jabatan_fungsionals', function (Blueprint $table) {
            $table->id();
            $table->uuid('sister_id')->unique(); // "id" field from SISTER response (id riwayat jabatan fungsional)
            $table->uuid('id_sdm')->index();
            $table->string('jabatan_fungsional')->nullable();
            $table->string('sk')->nullable();
            $table->string('tanggal_mulai')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatan_fungsionals');
    }
};
