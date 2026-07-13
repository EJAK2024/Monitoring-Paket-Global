<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'admin0_1@gmail.com')->doesntExist()) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin0_1@gmail.com',
                'password' => Hash::make('adminmonitoring001'),
                'is_admin' => true,
            ]);

            $this->command->info('Admin account created: admin0_1@gmail.com / adminmonitoring001');
        }
    }
}
