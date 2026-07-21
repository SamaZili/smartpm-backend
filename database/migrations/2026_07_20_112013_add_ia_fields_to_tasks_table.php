<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // On ajoute les colonnes SANS 'after' pour éviter les erreurs si la colonne de référence n'existe pas
            
            if (!Schema::hasColumn('tasks', 'status')) {
                $table->string('status')->nullable()->default('a_faire');
            }
            
            if (!Schema::hasColumn('tasks', 'complexity')) {
                $table->string('complexity')->nullable()->default('moyenne');
            }
            
            if (!Schema::hasColumn('tasks', 'transactions')) {
                $table->unsignedInteger('transactions')->default(0);
            }
            
            if (!Schema::hasColumn('tasks', 'entities')) {
                $table->unsignedInteger('entities')->default(0);
            }
            
            if (!Schema::hasColumn('tasks', 'team_exp')) {
                $table->unsignedInteger('team_exp')->default(0);
            }
            
            if (!Schema::hasColumn('tasks', 'manager_exp')) {
                $table->unsignedInteger('manager_exp')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // On supprime les colonnes uniquement si elles existent
            if (Schema::hasColumn('tasks', 'manager_exp')) $table->dropColumn('manager_exp');
            if (Schema::hasColumn('tasks', 'team_exp')) $table->dropColumn('team_exp');
            if (Schema::hasColumn('tasks', 'entities')) $table->dropColumn('entities');
            if (Schema::hasColumn('tasks', 'transactions')) $table->dropColumn('transactions');
            if (Schema::hasColumn('tasks', 'complexity')) $table->dropColumn('complexity');
            if (Schema::hasColumn('tasks', 'status')) $table->dropColumn('status');
        });
    }
};