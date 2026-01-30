<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-super-admin 
                            {--name= : Имя пользователя}
                            {--email= : Email пользователя}
                            {--password= : Пароль пользователя}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создать пользователя с правами супер-администратора';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name') ?: $this->ask('Введите имя пользователя');
        $email = $this->option('email') ?: $this->ask('Введите email');
        $password = $this->option('password') ?: $this->secret('Введите пароль (минимум 8 символов)');

        // Валидация
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('Ошибки валидации:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('  - ' . $error);
            }
            return 1;
        }

        // Создание пользователя
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
            'is_super_admin' => true,
        ]);

        $this->info("✓ Супер-администратор успешно создан!");
        $this->line("  ID: {$user->id}");
        $this->line("  Имя: {$user->name}");
        $this->line("  Email: {$user->email}");
        $this->line("  Администратор: " . ($user->is_admin ? 'Да' : 'Нет'));
        $this->line("  Супер-администратор: " . ($user->is_super_admin ? 'Да' : 'Нет'));

        return 0;
    }
}
