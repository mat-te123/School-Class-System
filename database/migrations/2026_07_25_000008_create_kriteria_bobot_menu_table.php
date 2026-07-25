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
        Schema::create('kriteria_bobot_menu', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('paket_menu_pilihan_id')->constrained('paket_menu_pilihan')->onDelete('cascade');
            $table->foreignUuid('master_mata_pelajaran_id')->constrained('master_mata_pelajaran')->onDelete('cascade');
            $table->decimal('bobot_persen', 5, 2)->default(100.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kriteria_bobot_menu');
    }
};
