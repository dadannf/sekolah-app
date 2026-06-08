<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateKepalaSekolahUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example:
     * php artisan user:create-kepsek --name="Kepala Sekolah" --email="kepsek@sekolah.test" --password="Secret123!"
     */
    protected $signature = 'user:create-kepsek
        {--name= : Nama user Kepala Sekolah}
        {--email= : Email/username untuk login}
        {--password= : Password (opsional, akan diprompt jika tidak diisi)}';

    /**
     * The console command description.
     */
    protected $description = 'Create a Kepala Sekolah user (role kepala_sekolah) with admin-equivalent access.';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nama Kepala Sekolah');
        $email = $this->option('email') ?: $this->ask('Email/username (untuk login)');

        if (!$name || !$email) {
            $this->error('Nama dan email wajib diisi.');
            return 1;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("Email/username '{$email}' sudah dipakai.");
            return 1;
        }

        $password = $this->option('password');
        if (!$password) {
            $password = $this->secret('Password (akan disimpan bcrypt)');
        }

        if (!$password || mb_strlen($password) < 6) {
            $this->error('Password minimal 6 karakter.');
            return 1;
        }

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role = 'kepala_sekolah';
        $user->save();

        $this->info('✅ User Kepala Sekolah berhasil dibuat.');
        $this->line("- ID: {$user->id}");
        $this->line("- Nama: {$user->name}");
        $this->line("- Email/Username: {$user->email}");
        $this->line("- Role: {$user->role}");

        return 0;
    }
}
