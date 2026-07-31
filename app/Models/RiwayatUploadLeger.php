<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatUploadLeger extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'riwayat_upload_leger';

    protected $fillable = [
        'kelas_asal_id',
        'nama_kelas',
        'angkatan',
        'file_name',
        'file_path',
        'jumlah_siswa',
        'status',
        'error_message',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_siswa' => 'integer',
        ];
    }

    /**
     * Relasi ke model KelasAsal.
     *
     * @return BelongsTo
     */
    public function kelasAsal(): BelongsTo
    {
        return $this->belongsTo(KelasAsal::class, 'kelas_asal_id');
    }

    /**
     * Relasi ke model User (Pengunggah).
     *
     * @return BelongsTo
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
