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
        Schema::create('detail_pendaftaran_pilihan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pendaftaran_pilihan_id')->constrained('pendaftaran_pilihan')->onDelete('cascade');
            $table->foreignUuid('paket_menu_pilihan_id')->constrained('paket_menu_pilihan')->onDelete('cascade');
            $table->integer('urutan_pilihan');

            $table->unique(['pendaftaran_pilihan_id', 'urutan_pilihan'], 'unique_pendaftaran_urutan');
            $table->unique(['pendaftaran_pilihan_id', 'paket_menu_pilihan_id'], 'unique_pendaftaran_paket');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_pendaftaran_pilihan');
    }
};
