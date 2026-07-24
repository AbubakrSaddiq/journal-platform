<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Author', 'slug' => 'author', 'description' => 'Can submit manuscripts'],
            ['name' => 'Reviewer', 'slug' => 'reviewer', 'description' => 'Can review submissions'],
            ['name' => 'Editor', 'slug' => 'editor', 'description' => 'Can manage submissions for a journal'],
            ['name' => 'Managing Editor', 'slug' => 'managing_editor', 'description' => 'Can manage entire journal'],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'System administrator'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}