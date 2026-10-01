<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaketMenuPilihan extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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
    public $timestamps = true;

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'periode_id',
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
            'kuota_kapasitas' => 'integer',
            'kuota_terisi' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Accessor untuk menghitung sisa kuota yang masih tersedia.
     */
    public function getKuotaTersisaAttribute(): int
    {
        return max(0, $this->kuota_kapasitas - $this->kuota_terisi);
    }

    /**
     * Relasi ke kriteria bobot menu.
     */
    public function kriteriaBobots(): HasMany
    {
        return $this->hasMany(KriteriaBobotMenu::class, 'paket_menu_pilihan_id');
    }

    /**
     * Relasi ke periode pendaftaran.
     */
    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodePendaftaran::class, 'periode_id');
    }
}
