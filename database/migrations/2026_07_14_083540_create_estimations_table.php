<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('estimations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->float('predicted_effort')->comment('Effort estimé (heures/jours)');
            $table->float('confidence_score')->nullable()->comment('Score de confiance du modèle');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('estimations');
    }
};