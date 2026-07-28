   <?php

   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;

   return new class extends Migration
   {
       public function up(): void
       {
           Schema::table('projects', function (Blueprint $table) {
               // Supprime la contrainte unique sur la colonne 'name'
               // Le nom de l'index est déduit du log : projects_name_unique
               $table->dropUnique('projects_name_unique');
           });
       }

       public function down(): void
       {
           Schema::table('projects', function (Blueprint $table) {
               // On la remet au cas où on voudrait annuler
               $table->unique('name');
           });
       }
   };