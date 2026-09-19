<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Parent = 'parent';
    case Student = 'student';
    case Admin = 'admin';
    case TenantAdmin = 'tenant_admin';
    case Teacher = 'teacher';
}
