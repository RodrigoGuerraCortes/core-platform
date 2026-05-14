<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = env('DEFAULT_ADMIN_PASSWORD', 'ChangeMe123!');

        $users = [
            [
                'name' => 'Rodrigo Guerra',
                'email' => 'rguerracortes@gmail.com',
            ],
            [
                'name' => 'Pablo Flores',
                'email' => 'pabfloresrojas@gmail.com',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'is_platform_admin' => true,
                ]
            );
        }
    }
}
