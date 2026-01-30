<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создание супер-администратора
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@eg-web.local'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('SuperAdmin123!'),
                'is_admin' => true,
                'is_super_admin' => true,
            ]
        );

        if ($superAdmin->wasRecentlyCreated) {
            $this->command->info('✓ Супер-администратор создан:');
            $this->command->line("  Email: superadmin@eg-web.local");
            $this->command->line("  Пароль: SuperAdmin123!");
        } else {
            $this->command->info('✓ Супер-администратор уже существует');
        }

        // Создание администратора
        $admin = User::firstOrCreate(
            ['email' => 'admin@eg-web.local'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('Admin123!'),
                'is_admin' => true,
                'is_super_admin' => false,
            ]
        );

        if ($admin->wasRecentlyCreated) {
            $this->command->info('✓ Администратор создан:');
            $this->command->line("  Email: admin@eg-web.local");
            $this->command->line("  Пароль: Admin123!");
        } else {
            $this->command->info('✓ Администратор уже существует');
        }

        $this->command->line('');
        $this->command->warn('⚠️ ВАЖНО: Измените пароли после первого входа!');
    }
}
