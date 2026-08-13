<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NilaiLegerSiswa>
 */
class NilaiLegerSiswaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\NilaiLegerSiswa::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siswa_id' => \App\Models\Siswa::factory(),
            'tahun_ajaran' => '2024/2025',
            'semester' => 'Ganjil',
            'rata_6_mapel' => 0,
            'rata_keseluruhan' => 0,
            'nilai_json' => [],
        ];
    }
}
