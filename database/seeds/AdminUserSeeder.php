<?php

namespace Database\Seeds;

use Illuminate\Support\Facades\Hash;
use IsekaiPHP\Models\User;

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
