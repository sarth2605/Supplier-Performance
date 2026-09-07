<?php
require_once __DIR__ . '/../config/database.php';
$db = get_db();

foreach (['performance_scores', 'quality_inspections', 'deliveries', 'purchase_orders'] as $t) {
    echo "=== Table {$t} Columns ===\n";
    $cols = $db->query("DESCRIBE {$t}")->fetchAll();
    foreach ($cols as $c) {
        echo "  - {$c['Field']} ({$c['Type']})\n";
    }
}
