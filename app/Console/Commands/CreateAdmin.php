<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    // Command signature
    protected $signature = 'admin:create';

    // Command description
    protected $description = 'Create a new admin interactively';

    public function handle()
    {
        $username = $this->ask('Enter admin username');
        $password = $this->secret('Enter admin password');

        // Check if username already exists
        if (Admin::where('username', $username)->exists()) {
            $this->error('Admin username already exists!');
            return 1;
        }

        // Create admin
        Admin::create([
            'username' => $username,
            'password' => Hash::make($password),
        ]);

        $this->info("Admin '$username' created successfully!");
        return 0;
    }
}
