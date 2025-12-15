<?php

namespace Database\Seeds;

use IsekaiPHP\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        User::create([
            'username' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
    }
}

