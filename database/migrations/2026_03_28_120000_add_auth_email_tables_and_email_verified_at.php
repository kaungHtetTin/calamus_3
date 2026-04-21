<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Password reset tokens, pending email verifications, and learners.email_verified_at.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('learners') && !Schema::hasColumn('learners', 'email_verified_at')) {
            Schema::table('learners', function (Blueprint $table) {
                $table->timestamp('email_verified_at')->nullable();
            });

            // Existing accounts with an email are treated as already verified.
            DB::table('learners')
                ->whereNotNull('learner_email')
                ->where('learner_email', '!=', '')
                ->update(['email_verified_at' => now()]);
        }

        if (!Schema::hasTable('password_resets')) {
            Schema::create('password_resets', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('learner_email_verifications')) {
            Schema::create('learner_email_verifications', function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();
                $table->string('user_id', 64)->index();
                $table->string('token');
                $table->timestamp('expires_at');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('learner_email_verifications');

        if (Schema::hasTable('password_resets')) {
            Schema::drop('password_resets');
        }

        if (Schema::hasTable('learners') && Schema::hasColumn('learners', 'email_verified_at')) {
            Schema::table('learners', function (Blueprint $table) {
                $table->dropColumn('email_verified_at');
            });
        }
    }
};
