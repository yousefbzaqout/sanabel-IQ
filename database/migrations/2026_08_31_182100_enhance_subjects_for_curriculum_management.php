<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->string('code')->nullable()->after('slug');
            $table->unsignedTinyInteger('grade_level')->nullable()->after('code');
            $table->string('icon')->nullable()->after('grade_level');
            $table->text('description')->nullable()->after('icon');

            $table->unique(['code', 'grade_level']);
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table): void {
            $table->dropUnique(['code', 'grade_level']);
            $table->dropColumn(['code', 'grade_level', 'icon', 'description']);
        });
    }
};
