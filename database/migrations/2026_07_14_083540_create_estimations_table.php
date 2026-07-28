<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->decimal('predicted_effort', 10, 2)->nullable(); // ← nullable()
            $table->decimal('estimated_hours', 10, 2)->nullable(); // ← nullable()
            $table->decimal('confidence_score', 5, 2)->nullable(); // ← nullable()
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimations');
    }
};