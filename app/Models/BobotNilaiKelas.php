<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BobotNilaiKelas extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'bobot_nilai_kelas';

    public $timestamps = false;

    protected $fillable = [
        'kelas_asal_id',
        'master_mata_pelajaran_id',
        'bobot_persen',
    ];

    protected $casts = [
        'bobot_persen' => 'float',
    ];

    public function kelasAsal(): BelongsTo
    {
        return $this->belongsTo(KelasAsal::class, 'kelas_asal_id');
    }

    public function masterMataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MasterMataPelajaran::class, 'master_mata_pelajaran_id');
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->masterMataPelajaran();
    }
}
