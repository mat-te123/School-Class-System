<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanPertukaran extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pengajuan_pertukaran';

    protected $fillable = [
        'id',
        'siswa_id',
        'periode_pendaftaran_id',
        'paket_asal_id',
        'paket_tujuan_id',
        'alasan',
        'dokumen_persetujuan_path',
        'status',
        'catatan_admin',
        'ditinjau_oleh',
        'tanggal_tinjauan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_tinjauan' => 'datetime',
        ];
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function periodePendaftaran(): BelongsTo
    {
        return $this->belongsTo(PeriodePendaftaran::class);
    }

    public function paketAsal(): BelongsTo
    {
        return $this->belongsTo(PaketMenuPilihan::class, 'paket_asal_id');
    }

    public function paketTujuan(): BelongsTo
    {
        return $this->belongsTo(PaketMenuPilihan::class, 'paket_tujuan_id');
    }

    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditinjau_oleh');
    }
}
