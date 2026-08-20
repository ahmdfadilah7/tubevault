<?php

namespace App\Services;

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class GoogleAuthService
{
    public function findOrCreate(SocialiteUser $googleUser): User
    {
        $existing = User::query()->where('google_id', $googleUser->getId())->first();

        if ($existing) {
            return $this->syncProfile($existing, $googleUser);
        }

        $byEmail = User::query()->where('email', $googleUser->getEmail())->first();

        if ($byEmail) {
            $byEmail->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => $byEmail->email_verified_at ?? now(),
            ]);

            return $byEmail->fresh();
        }

        return User::query()->create([
            'google_id' => $googleUser->getId(),
            'name' => $googleUser->getName() ?? 'Pengguna Google',
            'email' => $googleUser->getEmail(),
            'avatar' => $googleUser->getAvatar(),
            'email_verified_at' => now(),
            'password' => null,
        ]);
    }

    private function syncProfile(User $user, SocialiteUser $googleUser): User
    {
        $user->update([
            'name' => $googleUser->getName() ?? $user->name,
            'avatar' => $googleUser->getAvatar() ?? $user->avatar,
        ]);

        return $user->fresh();
    }
}
