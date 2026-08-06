<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterMataPelajaran extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'master_mata_pelajaran';

    public $timestamps = true;

    protected $fillable = [
        'kode_mapel',
        'nama_mapel',
        'kelompok_mapel',
        'is_tiebreaker_default',
        'is_active',
    ];

    protected $casts = [
        'is_tiebreaker_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke kriteria bobot menu.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function kriteriaBobots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KriteriaBobotMenu::class, 'master_mata_pelajaran_id');
    }
}
