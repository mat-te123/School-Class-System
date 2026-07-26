<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Siswa extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    /**
     * Nama tabel database yang digunakan oleh model ini.
     *
     * @var string
     */
    protected $table = 'siswa';

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nisn',
        'nis',
        'nama_lengkap',
        'kelas_asal',
        'jenis_kelamin',
        'tanggal_lahir',
        'is_active',
        'password',
    ];

    /**
     * Atribut yang disembunyikan untuk serialisasi.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
