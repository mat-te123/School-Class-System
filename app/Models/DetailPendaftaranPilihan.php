<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPendaftaranPilihan extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'detail_pendaftaran_pilihan';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'pendaftaran_pilihan_id',
        'paket_menu_pilihan_id',
        'urutan_pilihan',
    ];

    public function pendaftaranPilihan(): BelongsTo
    {
        return $this->belongsTo(PendaftaranPilihan::class);
    }

    public function paketMenuPilihan(): BelongsTo
    {
        return $this->belongsTo(PaketMenuPilihan::class);
    }
}
