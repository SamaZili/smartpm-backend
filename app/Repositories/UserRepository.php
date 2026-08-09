<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class UserRepository
{
    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'type' => $data['type'] ?? 'chef_de_projet',
            'email_verified_at' => now(),
            'email_verification_token' => Str::random(60),
        ]);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function generateResetToken(User $user): string
    {
        $token = Str::random(60);
        $user->reset_password_token = $token;
        $user->reset_password_token_created_at = now();
        $user->save();
        return $token;
    }

    public function resetPassword(User $user, string $newPassword): void
    {
        $user->password = $newPassword;
        $user->reset_password_token = null;
        $user->reset_password_token_created_at = null;
        $user->save();
    }

    public function findByResetToken(string $token): ?User
    {
        $user = User::where('reset_password_token', $token)->first();
        
        if (!$user || !$user->reset_password_token_created_at) {
            return null;
        }

        $expiresAt = $user->reset_password_token_created_at->addMinutes(60);
        if ($expiresAt->isPast()) {
            return null;
        }

        return $user;
    }
}