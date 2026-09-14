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
        Schema::table('paket_menu_pilihan', function (Blueprint $table) {
            $table->foreignUuid('periode_id')->nullable()->constrained('periode_pendaftaran')->onDelete('cascade')->after('id');
            
            // Hapus unique constraint yang lama
            $table->dropUnique('paket_menu_pilihan_nama_menu_unique');
            
            // Tambahkan unique constraint kombinasi
            $table->unique(['nama_menu', 'periode_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paket_menu_pilihan', function (Blueprint $table) {
            $table->dropUnique(['nama_menu', 'periode_id']);
            $table->unique('nama_menu', 'paket_menu_pilihan_nama_menu_unique');
            $table->dropForeign(['periode_id']);
            $table->dropColumn('periode_id');
        });
    }
};
