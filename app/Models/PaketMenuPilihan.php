<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaketMenuPilihan extends Model
{
    use HasFactory, HasUuids;

    /**
     * Nama tabel database yang digunakan oleh model ini.
     *
     * @var string
     */
    protected $table = 'paket_menu_pilihan';

    /**
     * Indikasi apakah model memiliki timestamp (created_at, updated_at).
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'kode_menu',
        'nama_menu',
        'rumpun',
        'kuota_kapasitas',
        'kuota_terisi',
        'is_active',
    ];

    /**
     * Casting atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kode_menu' => 'integer',
            'kuota_kapasitas' => 'integer',
            'kuota_terisi' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Accessor untuk menghitung sisa kuota yang masih tersedia.
     *
     * @return int
     */
    public function getKuotaTersisaAttribute(): int
    {
        return max(0, $this->kuota_kapasitas - $this->kuota_terisi);
    }

    /**
     * Relasi ke kriteria bobot menu.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function kriteriaBobots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KriteriaBobotMenu::class, 'paket_menu_pilihan_id');
    }
}
