<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@journalplatform.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'affiliation' => 'Journal Platform',
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $admin->roles()->syncWithoutDetaching([
                $adminRole->id => ['journal_id' => null]
            ]);
        }

        // Create managing editor
        $editor = User::updateOrCreate(
            ['email' => 'editor@journalplatform.com'],
            [
                'name' => 'Managing Editor',
                'password' => Hash::make('password'),
                'affiliation' => 'Journal Platform',
                'email_verified_at' => now(),
            ]
        );

        $editorRole = Role::where('slug', 'managing_editor')->first();
        if ($editorRole) {
            $editor->roles()->syncWithoutDetaching([
                $editorRole->id => ['journal_id' => null]
            ]);
        }

        $this->command->info('Admin users created successfully.');
    }
}