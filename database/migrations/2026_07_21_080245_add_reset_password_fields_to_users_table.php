<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'reset_password_token')) {
                $table->string('reset_password_token')->nullable()->after('email_verification_token');
            }
            if (!Schema::hasColumn('users', 'reset_password_token_created_at')) {
                $table->timestamp('reset_password_token_created_at')->nullable()->after('reset_password_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reset_password_token', 'reset_password_token_created_at']);
        });
    }
};