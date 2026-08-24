<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilSeleksi extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hasil_seleksi';

    public $timestamps = false; // tabel memakai kolom tanggal_diproses

    protected $fillable = [
        'id',
        'siswa_id',
        'paket_menu_pilihan_id',
        'pilihan_ke_diterima',
        'rank_pada_pilihan',
        'skor_penempatan',
        'rata_6_mapel',
        'mekanisme',
        'tanggal_diproses',
    ];

    protected $casts = [
        'pilihan_ke_diterima' => 'integer',
        'rank_pada_pilihan' => 'integer',
        'skor_penempatan' => 'float',
        'rata_6_mapel' => 'float',
        'tanggal_diproses' => 'datetime',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function paketMenuPilihan(): BelongsTo
    {
        return $this->belongsTo(PaketMenuPilihan::class);
    }
}
