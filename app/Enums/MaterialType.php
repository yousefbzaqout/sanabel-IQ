<?php

declare(strict_types=1);

namespace App\Enums;

enum MaterialType: string
{
    case Exam = 'exam';
    case Summary = 'summary';
    case Worksheet = 'worksheet';
}
