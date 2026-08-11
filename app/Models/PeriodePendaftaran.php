<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodePendaftaran extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'periode_pendaftaran';

    public $timestamps = false;

    protected $fillable = [
        'nama_periode',
        'tahun_ajaran',
        'gelombang',
        'max_pilihan_siswa',
        'tanggal_buka',
        'tanggal_tutup',
        'status_pengumuman',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_pilihan_siswa' => 'integer',
            'tanggal_buka' => 'datetime',
            'tanggal_tutup' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}