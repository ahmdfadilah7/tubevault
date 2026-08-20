<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteAdminCommand extends Command
{
    protected $signature = 'admin:promote
        {email : Email pengguna yang akan dijadikan admin}
        {--password= : Set / reset password (opsional)}
        {--name= : Nama tampilan (opsional, hanya jika user baru)}';

    protected $description = 'Jadikan user sebagai admin panel (/my-panel), atau buat akun admin baru';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $password = $this->option('password');
        $name = $this->option('name') ?: 'TubeVault Admin';

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            if (! $password) {
                $this->error('User belum ada. Sertakan --password untuk membuat akun admin baru.');

                return self::FAILURE;
            }

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);

            $this->info("Admin baru dibuat: {$user->email}");

            return self::SUCCESS;
        }

        $payload = ['is_admin' => true];

        if ($password) {
            $payload['password'] = $password;
        }

        $user->update($payload);

        $this->info("{$user->email} sekarang adalah admin.");

        if (! $user->password && ! $password) {
            $this->warn('Akun ini belum punya password (Google-only). Set dengan --password=...');
        }

        return self::SUCCESS;
    }
}
