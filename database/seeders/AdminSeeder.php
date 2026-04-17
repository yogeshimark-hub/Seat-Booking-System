<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => 'admin123',
                'role' => 'admin',
            ]
        );

        Admin::updateOrCreate(
            ['email' => 'vendor@example.com'],
            [
                'name' => 'Default Vendor',
                'password' => 'vendor123',
                'role' => 'vendor',
            ]
        );
    }
}
