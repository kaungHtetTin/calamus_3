<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

try {
    if (Schema::hasTable('learners')) {
        echo "Table 'learners' exists.\n";
        $columns = Schema::getColumnListing('learners');
        foreach ($columns as $column) {
            $type = Schema::getColumnType('learners', $column);
            echo "- $column ($type)\n";
        }
    } else {
        echo "Table 'learners' does not exist.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
