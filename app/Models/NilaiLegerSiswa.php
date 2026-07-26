<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NilaiLegerSiswa extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'nilai_leger_siswa';

    public $timestamps = false;

    protected $fillable = [
        'siswa_id',
        'tahun_ajaran',
        'semester',
        'rata_6_mapel',
        'rata_keseluruhan',
        'nilai_json',
    ];

    protected $casts = [
        'rata_6_mapel' => 'float',
        'rata_keseluruhan' => 'float',
        'nilai_json' => 'array',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailNilaiSiswa::class, 'nilai_leger_siswa_id');
    }
}
