<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyeksi_universitas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_universitas', 200);
            $table->string('singkatan', 20)->nullable();
            $table->string('akreditasi', 20)->nullable();
            $table->string('lokasi_kota', 100)->nullable();
            $table->string('lokasi_provinsi', 100)->nullable();
            $table->string('website', 255)->nullable();
            $table->text('deskripsi')->nullable();
            $table->integer('tahun_data')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyeksi_universitas');
    }
};
