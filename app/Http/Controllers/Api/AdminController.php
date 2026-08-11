<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AdminController extends BaseController
{
    /**
     * Get all users with their roles.
     */
    public function users()
    {
        $users = User::with('roles')->get()->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'affiliation' => $user->affiliation,
            'roles' => $user->roles->map(fn($role) => [
                'name' => $role->name,
                'slug' => $role->slug,
            ]),
        ]);

        return response()->json(['users' => $users]);
    }

    /**
     * Assign role to user.
     */
    public function assignRole(User $user)
    {
        $validated = request()->validate([
            'role_slug' => 'required|string|exists:roles,slug',
        ]);

        $role = Role::where('slug', $validated['role_slug'])->first();

        // Check if already has role
        $exists = DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'User already has this role'
            ], 422);
        }

        DB::table('user_roles')->insert([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'journal_id' => null,
        ]);

        return response()->json([
            'message' => "Role '{$role->name}' assigned to {$user->name}",
        ]);
    }

    /**
     * Remove role from user.
     */
    public function removeRole(User $user, string $roleSlug)
    {
        $role = Role::where('slug', $roleSlug)->first();

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        DB::table('user_roles')
            ->where('user_id', $user->id)
            ->where('role_id', $role->id)
            ->delete();

        return response()->json([
            'message' => "Role '{$role->name}' removed from {$user->name}",
        ]);
    }
}