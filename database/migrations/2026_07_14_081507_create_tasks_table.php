<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('transactions', 10, 2)->default(0); // ← Ajoutez cette ligne
            $table->integer('entities')->default(0); // ← Ajoutez cette ligne
            $table->integer('team_exp')->default(0); // ← Ajoutez cette ligne
            $table->integer('manager_exp')->default(0); // ← Ajoutez cette ligne
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};