   <?php

   use Illuminate\Database\Migrations\Migration;
   use Illuminate\Database\Schema\Blueprint;
   use Illuminate\Support\Facades\Schema;

   return new class extends Migration {
       public function up(): void {
           Schema::table('tasks', function (Blueprint $table) {
               if (!Schema::hasColumn('tasks', 'transactions')) {
                   $table->integer('transactions')->nullable()->after('description');
               }
               if (!Schema::hasColumn('tasks', 'entities')) {
                   $table->integer('entities')->nullable()->after('transactions');
               }
               if (!Schema::hasColumn('tasks', 'team_exp')) {
                   $table->integer('team_exp')->nullable()->after('entities');
               }
               if (!Schema::hasColumn('tasks', 'manager_exp')) {
                   $table->integer('manager_exp')->nullable()->after('team_exp');
               }
           });
       }

       public function down(): void {
           Schema::table('tasks', function (Blueprint $table) {
               $table->dropColumn(['transactions', 'entities', 'team_exp', 'manager_exp']);
           });
       }
   };