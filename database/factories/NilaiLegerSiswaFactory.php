<?php

namespace Database\Factories;

use App\Models\NilaiLegerSiswa;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NilaiLegerSiswa>
 */
class NilaiLegerSiswaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = NilaiLegerSiswa::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siswa_id' => Siswa::factory(),
            'tahun_ajaran' => '2024/2025',
            'semester' => 'Ganjil',
            'rata_6_mapel' => 0,
            'rata_keseluruhan' => 0,
            'nilai_json' => [],
        ];
    }
}
