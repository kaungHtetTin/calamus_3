<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop legacy notification tables.
     * Run after create_notifications_table.
     */
    public function up()
    {
        Schema::dropIfExists('notification');
        Schema::dropIfExists('notification_action');
    }

    public function down()
    {
        // Cannot restore dropped tables without schema
    }
};
