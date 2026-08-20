<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProyeksiUniversitas extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'proyeksi_universitas';

    protected $fillable = [
        'nama_universitas',
        'singkatan',
        'akreditasi',
        'lokasi_kota',
        'lokasi_provinsi',
        'website',
        'deskripsi',
        'tahun_data',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'tahun_data' => 'integer',
    ];

    public function programStudis(): HasMany
    {
        return $this->hasMany(ProgramStudi::class, 'proyeksi_universitas_id');
    }
}
