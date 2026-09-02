<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

class AuthRedirectResolver
{
    public function homeUrl(User $user): string
    {
        if ($user->isAdmin()) {
            return '/admin';
        }

        return '/parent';
    }
}
