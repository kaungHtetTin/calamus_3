<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $this->progress('Skipping vocab tables user_id migration (data migration).');
        return;
    }

    public function down()
    {
        $this->progress('Skipping vocab tables revert (data migration).');
        return;
    }

    private function progress(string $message): void
    {
        $line = '[Migration] ' . $message;
        if (isset($this->command) && method_exists($this->command, 'getOutput')) {
            $this->command->getOutput()->writeln($line);
            return;
        }
        echo $line . PHP_EOL;
    }
};
