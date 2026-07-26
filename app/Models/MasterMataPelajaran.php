<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterMataPelajaran extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'master_mata_pelajaran';

    public $timestamps = false;

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
}
