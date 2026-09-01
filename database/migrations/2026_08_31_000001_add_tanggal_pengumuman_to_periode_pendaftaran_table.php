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
        Schema::table('periode_pendaftaran', function (Blueprint $table) {
            $table->timestamp('tanggal_pengumuman')->nullable()->after('tanggal_tutup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_pendaftaran', function (Blueprint $table) {
            $table->dropColumn('tanggal_pengumuman');
        });
    }
};
