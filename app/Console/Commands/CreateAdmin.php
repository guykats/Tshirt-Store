<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('app:create-admin')]
#[Description('Interactively create an admin user (password entered via hidden prompt, never passed as an argument)')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email],
            ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'unique:users,email']]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = $this->secret('Password (min 8 characters, input hidden)');
        $confirm = $this->secret('Confirm password');

        if ($password !== $confirm) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        // forceCreate bypasses mass-assignment protection intentionally: 'role' is not
        // fillable from web input by design, but this command only accepts trusted
        // server-side console input.
        User::forceCreate([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'admin',
        ]);

        $this->info("Admin user {$email} created successfully.");

        return self::SUCCESS;
    }
}
