<?php

declare(strict_types=1);

namespace App\Enums;

enum ParentGoalStatus: string
{
    case Pending = 'pending';
    case Achieved = 'achieved';
    case Expired = 'expired';
}
