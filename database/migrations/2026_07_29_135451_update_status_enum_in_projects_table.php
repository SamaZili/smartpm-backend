<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter 'en_pause' aux valeurs autorisées de l'ENUM
        DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('en_cours', 'en_pause', 'termine') DEFAULT 'en_cours'");
    }

    public function down(): void
    {
        // Revenir à l'état précédent en cas d'annulation
        DB::statement("ALTER TABLE projects MODIFY COLUMN status ENUM('en_cours', 'termine') DEFAULT 'en_cours'");
    }
};