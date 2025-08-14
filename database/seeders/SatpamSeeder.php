<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Satpam;

class SatpamSeeder extends Seeder
{
    public function run(): void
    {
        Satpam::create([
            'username' => 'admin',
            'password' => Hash::make('admin123'), // <- ubah jika perlu
            'nama' => 'Administrator',
            'role' => 'Admin'
        ]);
    }
}
