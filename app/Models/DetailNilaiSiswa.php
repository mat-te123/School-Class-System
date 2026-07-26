<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailNilaiSiswa extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'detail_nilai_siswa';

    public $timestamps = false;

    protected $fillable = [
        'nilai_leger_siswa_id',
        'master_mata_pelajaran_id',
        'nilai_angka',
        'predikat',
    ];

    protected $casts = [
        'nilai_angka' => 'float',
    ];

    public function leger(): BelongsTo
    {
        return $this->belongsTo(NilaiLegerSiswa::class, 'nilai_leger_siswa_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MasterMataPelajaran::class, 'master_mata_pelajaran_id');
    }
}
