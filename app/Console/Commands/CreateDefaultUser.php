<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class CreateDefaultUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command creates a default user with the email of info@haad.uz and password of haad@123';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Welcome to the user creation command!");
        $name = $this->ask('Enter name (default: Admin):', 'Admin');
        $email = $this->ask('Enter email (default: info@haad.uz):', 'info@haad.uz');
        $password = $this->secret('Enter password (default: haad@123):') ?? 'haad@123';

        // Create the user
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $this->error('A user with this email already exists!');
            return;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email, 
            'password' => Hash::make($password),
        ]);

        // Show the created user details
        $this->info('User created successfully with the following details:');
        $this->table(
            ['Name', 'Email', 'Password'],
            [[$user->name, $user->email, $password]]
        );
    }
}
