   <?php

   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;

   return new class extends Migration
   {
       public function up(): void
       {
           Schema::table('users', function (Blueprint $table) {
               // On force la colonne à être un string de 50 caractères avec une valeur par défaut
               $table->string('type', 50)->default('chef_de_projet')->change();
           });
       }

       public function down(): void
       {
           Schema::table('users', function (Blueprint $table) {
               $table->string('type', 10)->default('chef_de_projet')->change();
           });
       }
   };