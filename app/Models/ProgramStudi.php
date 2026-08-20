<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProgramStudi extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'program_studi';

    protected $fillable = [
        'proyeksi_universitas_id',
        'nama_prodi',
        'jenjang',
        'akreditasi_prodi',
        'daya_tampung',
        'peminat_tahun_lalu',
        'kelompok_saintek_soshum',
        'is_active',
    ];

    protected $casts = [
        'is_active'          => 'boolean',
        'daya_tampung'       => 'integer',
        'peminat_tahun_lalu' => 'integer',
    ];

    public function proyeksiUniversitas(): BelongsTo
    {
        return $this->belongsTo(ProyeksiUniversitas::class, 'proyeksi_universitas_id');
    }
}
