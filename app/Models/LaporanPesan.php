<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanPesan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'laporan_pesan';

    protected $fillable = [
        'user_id',
        'siswa_id',
        'nisn',
        'nama',
        'kelas',
        'judul',
        'kategori',
        'pesan',
        'lampiran_path',
        'status',
        'catatan_penanganan',
        'ditangani_oleh',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    /**
     * Relasi ke model User (Pelapor Admin/Guru).
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke model Siswa (Pelapor Siswa).
     *
     * @return BelongsTo
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    /**
     * Relasi ke model User (Petugas yang menangani).
     *
     * @return BelongsTo
     */
    public function penangan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditangani_oleh');
    }
}
