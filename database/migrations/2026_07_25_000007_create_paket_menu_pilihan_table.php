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
        Schema::create('paket_menu_pilihan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_menu', 50)->unique();
            $table->enum('rumpun', ['eksakta', 'sosial']);
            $table->integer('kuota_kapasitas')->default(36);
            $table->integer('kuota_terisi')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paket_menu_pilihan');
    }
};
