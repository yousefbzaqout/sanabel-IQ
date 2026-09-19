<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mastery_concepts', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('label');
            $table->text('hint_template');
            $table->smallInteger('threshold')->default(2);
            $table->jsonb('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mastery_concepts');
    }
};
