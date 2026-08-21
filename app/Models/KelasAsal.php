<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KelasAsal extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'kelas_asal';

    protected $fillable = [
        'nama_kelas',
        'tingkat',
    ];

    /**
     * Relasi ke model Siswa.
     *
     * @return HasMany
     */
    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class, 'kelas_asal_id');
    }
}
