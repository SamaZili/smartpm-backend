<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('backend'); // Ex: frontend, backend, database...
            $table->string('complexity')->default('moyenne'); // Ex: faible, moyenne, elevee
            $table->string('size')->default('moyenne'); // Ex: petite, moyenne, grande
            $table->enum('status', ['a_faire', 'en_cours', 'terminee'])->default('a_faire');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('tasks');
    }
};