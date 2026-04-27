<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('social_accounts')) {
            return;
        }

        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 32);
            $table->string('provider_user_id', 191);
            $table->string('provider_email', 255)->nullable();
            $table->string('avatar_url', 2048)->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_user_id']);
            $table->index('user_id');
            $table->index(['provider', 'provider_email']);
        });

        if (! Schema::hasTable('learners') || ! Schema::hasColumn('learners', 'user_id')) {
            return;
        }

        try {
            Schema::table('social_accounts', function (Blueprint $table) {
                $table->foreign('user_id')
                    ->references('user_id')
                    ->on('learners')
                    ->onDelete('cascade');
            });
        } catch (\Throwable $e) {
        }
    }

    public function down()
    {
        Schema::dropIfExists('social_accounts');
    }
};
