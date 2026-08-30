<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasVectorSearch;
use Database\Factories\MaterialChunkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['parent_material_id', 'content', 'embedding', 'chunk_index'])]
class MaterialChunk extends Model
{
    /** @use HasFactory<MaterialChunkFactory> */
    use HasFactory;

    use HasVectorSearch;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
        ];
    }

    /** @return BelongsTo<ParentMaterial, $this> */
    public function parentMaterial(): BelongsTo
    {
        return $this->belongsTo(ParentMaterial::class);
    }

    /**
     * @return Attribute<string, string>
     */
    protected function embedding(): Attribute
    {
        return Attribute::make(
            set: function (array|string $value): string {
                if (is_array($value)) {
                    return '['.implode(',', array_map(
                        static fn (int|float $item): string => (string) $item,
                        $value,
                    )).']';
                }

                return $value;
            },
        );
    }
}
