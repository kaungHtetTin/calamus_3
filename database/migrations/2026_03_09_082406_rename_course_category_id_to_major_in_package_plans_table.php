<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('package_plans', function (Blueprint $table) {
            // Drop foreign key if it exists
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'package_plans' 
                AND CONSTRAINT_TYPE = 'FOREIGN KEY' 
                AND CONSTRAINT_NAME = 'package_plans_ibfk_1'
            ");

            if (!empty($foreignKeys)) {
                $table->dropForeign('package_plans_ibfk_1');
            }

            if (Schema::hasColumn('package_plans', 'course_category_id')) {
                $table->renameColumn('course_category_id', 'major');
            }
        });

        // Temporarily change to string to hold both IDs and Keywords
        Schema::table('package_plans', function (Blueprint $table) {
            $table->string('major')->change();
        });

        // Map existing IDs to keywords from course_categories
        $categories = DB::table('course_categories')->get();
        foreach ($categories as $category) {
            DB::table('package_plans')
                ->where('major', (string)$category->id)
                ->update(['major' => $category->keyword]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Map keywords back to IDs
        $categories = DB::table('course_categories')->get();
        foreach ($categories as $category) {
            DB::table('package_plans')
                ->where('major', $category->keyword)
                ->update(['major' => (string)$category->id]);
        }

        Schema::table('package_plans', function (Blueprint $table) {
            $table->integer('major')->change();
            $table->renameColumn('major', 'course_category_id');
            
            // Re-add foreign key
            $table->foreign('course_category_id', 'package_plans_ibfk_1')
                  ->references('id')->on('course_categories')
                  ->onDelete('cascade');
        });
    }
};
