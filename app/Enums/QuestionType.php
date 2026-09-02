<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestionType: string
{
    case Mcq = 'mcq';
    case TrueFalse = 'true_false';
    case FillBlank = 'fill_blank';
}
