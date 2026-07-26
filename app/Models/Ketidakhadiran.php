<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ketidakhadiran extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ketidakhadiran';

    public $timestamps = false;

    protected $fillable = [
        'siswa_id',
        'sakit',
        'izin',
        'alpa',
    ];

    protected $casts = [
        'sakit' => 'integer',
        'izin' => 'integer',
        'alpa' => 'integer',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
