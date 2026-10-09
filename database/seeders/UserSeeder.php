<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Penumpang',
                'email'    => 'penumpang@rombongin.test',
                'password' => Hash::make('password123'),
                'role'     => 'penumpang',
                'status'   => 'aktif',
            ],
            [
                'name'     => 'Pengemudi',
                'email'    => 'pengemudi@rombongin.test',
                'password' => Hash::make('password123'),
                'role'     => 'pengemudi',
                'status'   => 'aktif',
            ],
            [
                'name'     => 'Admin',
                'email'    => 'admin@rombongin.test',
                'password' => Hash::make('password123'),
                'role'     => 'admin',
                'status'   => 'aktif',
            ],
            [
                'name'     => 'Super Admin',
                'email'    => 'superadmin@rombongin.test',
                'password' => Hash::make('password123'),
                'role'     => 'super_admin',
                'status'   => 'aktif',
            ],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
