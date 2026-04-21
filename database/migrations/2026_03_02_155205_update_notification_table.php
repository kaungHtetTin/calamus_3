<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Reset existing notifications as per user request to standardize
        DB::table('notification')->truncate();

        Schema::table('notification', function (Blueprint $table) {
            // Standardization for compact navigation on click
            $table->string('target_type')->nullable()->after('comment_id');
            $table->string('target_id')->nullable()->after('target_type'); // Using string for flexibility
            $table->json('metadata')->nullable()->after('target_id');
            
            // owner_id and writer_id should ideally be consistent with user_id in learners
            // They are already bigInt in schema, which matches learners.user_id
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notification', function (Blueprint $table) {
            $table->dropColumn(['target_type', 'target_id', 'metadata']);
        });
    }
};
