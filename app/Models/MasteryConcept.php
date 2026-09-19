<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'label',
    'hint_template',
    'threshold',
    'meta',
])]
class MasteryConcept extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'threshold' => 'integer',
            'meta' => 'array',
        ];
    }
}
