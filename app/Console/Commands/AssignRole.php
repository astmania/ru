<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AssignRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-role 
                            {email : Email пользователя}
                            {--admin : Назначить роль администратора}
                            {--super-admin : Назначить роль супер-администратора}
                            {--moderator : Назначить роль модератора (Шежіре)}
                            {--remove-admin : Убрать роль администратора}
                            {--remove-super-admin : Убрать роль супер-администратора}
                            {--remove-moderator : Убрать роль модератора}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Назначить или убрать роль пользователю';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Пользователь с email {$email} не найден!");
            return 1;
        }

        $changes = false;

        // Назначение ролей
        if ($this->option('admin')) {
            $user->is_admin = true;
            $changes = true;
            $this->info("✓ Роль администратора назначена");
        }

        if ($this->option('super-admin')) {
            $user->is_admin = true; // Супер-админ автоматически становится админом
            $user->is_super_admin = true;
            $changes = true;
            $this->info("✓ Роль супер-администратора назначена");
        }

        if ($this->option('moderator')) {
            $user->is_moderator = true;
            $changes = true;
            $this->info("✓ Роль модератора (Шежіре) назначена");
        }

        // Удаление ролей
        if ($this->option('remove-admin')) {
            $user->is_admin = false;
            // Если убираем админа, убираем и супер-админа
            if ($user->is_super_admin) {
                $user->is_super_admin = false;
                $this->info("✓ Роль супер-администратора также убрана");
            }
            $changes = true;
            $this->info("✓ Роль администратора убрана");
        }

        if ($this->option('remove-super-admin')) {
            $user->is_super_admin = false;
            $changes = true;
            $this->info("✓ Роль супер-администратора убрана");
        }

        if ($this->option('remove-moderator')) {
            $user->is_moderator = false;
            $changes = true;
            $this->info("✓ Роль модератора убрана");
        }

        if (!$changes) {
            $this->warn('Не указаны опции для изменения ролей');
            $this->line('');
            $this->line('Текущие роли пользователя:');
            $this->line("  Администратор: " . ($user->is_admin ? 'Да' : 'Нет'));
            $this->line("  Супер-администратор: " . ($user->is_super_admin ? 'Да' : 'Нет'));
            $this->line("  Модератор (Шежіре): " . ($user->is_moderator ? 'Да' : 'Нет'));
            $this->line('');
            $this->line('Использование:');
            $this->line('  php artisan user:assign-role {email} --admin');
            $this->line('  php artisan user:assign-role {email} --super-admin');
            $this->line('  php artisan user:assign-role {email} --remove-admin');
            $this->line('  php artisan user:assign-role {email} --remove-super-admin');
            $this->line('  php artisan user:assign-role {email} --moderator');
            $this->line('  php artisan user:assign-role {email} --remove-moderator');
            return 0;
        }

        $user->save();

        $this->line('');
        $this->info("✓ Роли пользователя обновлены:");
        $this->line("  Имя: {$user->name}");
        $this->line("  Email: {$user->email}");
        $this->line("  Администратор: " . ($user->is_admin ? 'Да' : 'Нет'));
        $this->line("  Супер-администратор: " . ($user->is_super_admin ? 'Да' : 'Нет'));
        $this->line("  Модератор (Шежіре): " . ($user->is_moderator ? 'Да' : 'Нет'));

        return 0;
    }
}
