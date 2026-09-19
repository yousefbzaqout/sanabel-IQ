<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('family_code', 6)->nullable()->unique()->after('role');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->string('pin_hash')->nullable()->after('avatar_path');
            $table->timestamp('login_enabled_at')->nullable()->after('pin_hash');
            $table->foreignId('login_user_id')
                ->nullable()
                ->after('login_enabled_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->unique('login_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('login_user_id');
            $table->dropColumn(['pin_hash', 'login_enabled_at']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['family_code']);
            $table->dropColumn('family_code');
        });
    }
};
