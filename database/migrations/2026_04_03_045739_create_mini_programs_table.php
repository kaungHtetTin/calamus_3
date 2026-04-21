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
        Schema::create('mini_programs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 225);
            $table->string('link_url', 500);
            $table->string('image_url', 500);
            $table->tinyInteger('function_type');
            $table->tinyInteger('function_id');
            $table->string('major', 10);
            $table->timestamps();
        });

        // Migrate data from functions to mini_programs
        if (Schema::hasTable('functions')) {
            $functions = DB::table('functions')->get();
            foreach ($functions as $function) {
                DB::table('mini_programs')->insert([
                    'id' => $function->id,
                    'title' => $function->title,
                    'link_url' => $function->link_url,
                    'image_url' => $function->image_url,
                    'function_type' => $function->function_type,
                    'function_id' => $function->function_id,
                    'major' => $function->major,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            // Drop the old functions table
            Schema::dropIfExists('functions');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Recreate the functions table if needed
        if (!Schema::hasTable('functions')) {
            Schema::create('functions', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('title', 225);
                $table->string('link_url', 500);
                $table->string('image_url', 500);
                $table->tinyInteger('function_type');
                $table->tinyInteger('function_id');
                $table->string('major', 10);
            });

            // Restore data from mini_programs to functions
            if (Schema::hasTable('mini_programs')) {
                $miniPrograms = DB::table('mini_programs')->get();
                foreach ($miniPrograms as $mp) {
                    DB::table('functions')->insert([
                        'id' => $mp->id,
                        'title' => $mp->title,
                        'link_url' => $mp->link_url,
                        'image_url' => $mp->image_url,
                        'function_type' => $mp->function_type,
                        'function_id' => $mp->function_id,
                        'major' => $mp->major,
                    ]);
                }
            }
        }
        
        Schema::dropIfExists('mini_programs');
    }
};
