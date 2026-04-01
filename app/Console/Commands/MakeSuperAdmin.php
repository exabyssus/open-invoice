<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeSuperAdmin extends Command
{
    protected $signature = 'make:super-admin
                            {--email= : Email of an existing user to promote}
                            {--create : Create a new super-admin user}';

    protected $description = 'Create or promote a user to super-admin';

    public function handle(): int
    {
        if ($this->option('create')) {
            return $this->createSuperAdmin();
        }

        if ($email = $this->option('email')) {
            return $this->promoteSuperAdmin($email);
        }

        $choice = $this->choice(
            'Would you like to create a new user or promote an existing one?',
            ['Create new user', 'Promote existing user'],
            0
        );

        return $choice === 'Create new user'
            ? $this->createSuperAdmin()
            : $this->promoteSuperAdmin();
    }

    private function createSuperAdmin(): int
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email');
        $password = $this->secret('Password');

        if (User::where('email', $email)->exists()) {
            $this->error("User with email {$email} already exists. Use --email to promote.");
            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->info("Super-admin {$email} created successfully.");
        return self::SUCCESS;
    }

    private function promoteSuperAdmin(?string $email = null): int
    {
        if (! $email) {
            $email = $this->ask('Email of the user to promote');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} not found.");
            return self::FAILURE;
        }

        if ($user->isSuperAdmin()) {
            $this->warn("{$email} is already a super-admin.");
            return self::SUCCESS;
        }

        $user->update(['is_super_admin' => true]);

        $this->info("{$email} has been promoted to super-admin.");
        return self::SUCCESS;
    }
}
