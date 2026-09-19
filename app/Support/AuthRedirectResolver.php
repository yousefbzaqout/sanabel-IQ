<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

class AuthRedirectResolver
{
    public function homeUrl(User $user): string
    {
        if ($user->canAccessAdminPanel()) {
            return '/admin';
        }

        if ($user->isTeacher()) {
            return '/teacher';
        }

        if ($user->isStudent()) {
            return '/student';
        }

        return '/parent';
    }

    /**
     * Prefer the role home URL when the session "intended" path is inaccessible.
     */
    public function redirectPath(User $user, Request $request): string
    {
        $home = $this->homeUrl($user);
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || $intended === '') {
            return $home;
        }

        $path = parse_url($intended, PHP_URL_PATH) ?? $intended;

        if ($this->pathAllowedForUser($user, $path)) {
            return $intended;
        }

        return $home;
    }

    private function pathAllowedForUser(User $user, string $path): bool
    {
        if (str_starts_with($path, '/admin')) {
            return $user->canAccessAdminPanel();
        }

        if (str_starts_with($path, '/teacher')) {
            return $user->isTeacher();
        }

        if (str_starts_with($path, '/parent')) {
            return $user->isParent();
        }

        if (str_starts_with($path, '/student')) {
            return $user->isStudent() || $user->isParent();
        }

        return true;
    }
}
