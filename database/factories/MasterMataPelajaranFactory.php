<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MasterMataPelajaran>
 */
class MasterMataPelajaranFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\MasterMataPelajaran::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_mapel' => fake()->unique()->lexify('????'),
            'nama_mapel' => fake()->sentence(3),
            'kelompok_mapel' => fake()->randomElement(['umum', 'pilihan', 'muatan_lokal']),
            'is_tiebreaker_default' => false,
            'is_active' => true,
        ];
    }
}
