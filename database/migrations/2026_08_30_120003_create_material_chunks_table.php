<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_chunks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_material_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->vector('embedding', 768);
            $table->integer('chunk_index');
            $table->timestamps();

            $table->vectorIndex('embedding');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_chunks');
    }
};
