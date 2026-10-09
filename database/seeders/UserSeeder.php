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
                'name'     => 'Masyarakat',
                'email'    => 'masyarakat@jombangtransit.test',
                'password' => Hash::make('password123'),
                'role'     => 'masyarakat',
                'status'   => 'aktif',
            ],
            [
                'name'     => 'Operator Jombang Transit',
                'email'    => 'operator@jombangtransit.test',
                'password' => Hash::make('password123'),
                'role'     => 'operator',
                'status'   => 'aktif',
            ],
            [
                'name'     => 'Admin',
                'email'    => 'admin@jombangtransit.test',
                'password' => Hash::make('password123'),
                'role'     => 'admin',
                'status'   => 'aktif',
            ],
            [
                'name'     => 'Super Admin',
                'email'    => 'superadmin@jombangtransit.test',
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