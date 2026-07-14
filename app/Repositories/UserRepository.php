<?php

namespace App\Repositories;

use App\Models\User; // <--- CETTE LIGNE EST INDISPENSABLE
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        return User::create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }
}