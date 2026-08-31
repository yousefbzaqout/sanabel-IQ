<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_materials', function (Blueprint $table): void {
            $table->foreignId('subject_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parent_materials', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('subject_id');
        });
    }
};
