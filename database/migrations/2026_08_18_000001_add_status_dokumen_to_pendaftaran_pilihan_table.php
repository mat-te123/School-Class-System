<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftaran_pilihan', function (Blueprint $table) {
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])
                  ->default('menunggu')
                  ->after('tanggal_submit');
            $table->text('catatan_penolakan')->nullable()->after('status');
            $table->string('dokumen_wali_path', 255)->nullable()->after('catatan_penolakan');
            $table->foreignUuid('ditinjau_oleh')->nullable()
                  ->constrained('users')->nullOnDelete()->after('dokumen_wali_path');
            $table->timestamp('tanggal_tinjauan')->nullable()->after('ditinjau_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('pendaftaran_pilihan', function (Blueprint $table) {
            // dropConstrainedForeignId() hanya cocok untuk kolom bernama `xxx_id`.
            // Karena kolom bernama 'ditinjau_oleh' (bukan 'ditinjau_oleh_id'), drop manual:
            $table->dropForeign(['ditinjau_oleh']);
            $table->dropColumn(['status', 'catatan_penolakan', 'dokumen_wali_path', 'ditinjau_oleh', 'tanggal_tinjauan']);
        });
    }
};
