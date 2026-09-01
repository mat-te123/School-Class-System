<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hasil_seleksi', function (Blueprint $table) {
            $table->boolean('is_manual_override')->default(false)->after('mekanisme');
            $table->text('catatan_perubahan')->nullable()->after('is_manual_override');
            $table->foreignUuid('diubah_oleh')->nullable()->after('catatan_perubahan')->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_perubahan')->nullable()->after('diubah_oleh');
        });

        Schema::table('periode_pendaftaran', function (Blueprint $table) {
            $table->boolean('is_hasil_final')->default(false)->after('status_pengumuman');
        });
    }

    public function down(): void
    {
        Schema::table('hasil_seleksi', function (Blueprint $table) {
            $table->dropForeign(['diubah_oleh']);
            $table->dropColumn(['is_manual_override', 'catatan_perubahan', 'diubah_oleh', 'tanggal_perubahan']);
        });

        Schema::table('periode_pendaftaran', function (Blueprint $table) {
            $table->dropColumn('is_hasil_final');
        });
    }
};
