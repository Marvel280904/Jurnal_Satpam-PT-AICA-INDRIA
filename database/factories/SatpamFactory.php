<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Satpam;

class SatpamFactory extends Factory
{
    protected $model = Satpam::class;

    public function definition(): array
    {
        return [
            'username' => $this->faker->unique()->userName(),
            'password' => Hash::make('password'),
            'foto'     => null,
            'nama'     => $this->faker->name(),
            'role'     => 'Satpam',
        ];
    }
}
