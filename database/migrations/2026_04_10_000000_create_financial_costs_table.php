<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('financial_costs')) {
            return;
        }

        Schema::create('financial_costs', function (Blueprint $table) {
            $table->id();
            $table->string('major', 32)->index();
            $table->string('title', 255);
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamp('spent_at')->index();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['major', 'spent_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('financial_costs');
    }
};

