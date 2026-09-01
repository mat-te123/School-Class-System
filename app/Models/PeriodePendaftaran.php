<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'tanggal_pengumuman',
        'tanggal_mulai_pertukaran',
        'tanggal_selesai_pertukaran',
        'status_pengumuman',
        'is_active',
        'is_hasil_final',
    ];

    protected function casts(): array
    {
        return [
            'max_pilihan_siswa' => 'integer',
            'tanggal_buka' => 'datetime',
            'tanggal_tutup' => 'datetime',
            'tanggal_pengumuman' => 'datetime',
            'tanggal_mulai_pertukaran' => 'datetime',
            'tanggal_selesai_pertukaran' => 'datetime',
            'is_active' => 'boolean',
            'is_hasil_final' => 'boolean',
        ];
    }

    /**
     * Mengecek apakah pengumuman hasil seleksi pada periode ini sudah terbuka untuk siswa.
     */
    public function isPengumumanDibuka(): bool
    {
        if ($this->status_pengumuman === 'AKTIF') {
            return true;
        }

        if ($this->tanggal_pengumuman && now()->gte($this->tanggal_pengumuman)) {
            return true;
        }

        return false;
    }

    /**
     * Scope untuk memfilter periode yang pengumumannya sudah dibuka (manual AKTIF atau jadwal waktu tercapai).
     */
    public function scopePengumumanDibuka(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status_pengumuman', 'AKTIF')
              ->orWhere(function ($sub) {
                  $sub->whereNotNull('tanggal_pengumuman')
                      ->where('tanggal_pengumuman', '<=', now());
              });
        });
    }
}