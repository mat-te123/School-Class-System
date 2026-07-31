<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KriteriaBobotMenu extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'kriteria_bobot_menu';

    public $timestamps = false;

    protected $fillable = [
        'paket_menu_pilihan_id',
        'master_mata_pelajaran_id',
        'bobot_persen',
    ];

    protected $casts = [
        'bobot_persen' => 'float',
    ];

    /**
     * Relasi ke model PaketMenuPilihan.
     *
     * @return BelongsTo
     */
    public function paketMenuPilihan(): BelongsTo
    {
        return $this->belongsTo(PaketMenuPilihan::class, 'paket_menu_pilihan_id');
    }

    /**
     * Relasi ke model MasterMataPelajaran.
     *
     * @return BelongsTo
     */
    public function masterMataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MasterMataPelajaran::class, 'master_mata_pelajaran_id');
    }
}
