<?php

require_once dirname(__DIR__) . '/../../main.inc.php';

global $db;

if (php_sapi_name() !== 'cli') {
    die("CLI only");
}

$limitDate = dol_time_plus_duree(dol_now(), -3, 'w');

$statusDraft = 0;

$delete = sprintf(
    "DELETE FROM %s%s  WHERE status = %d AND delivery_date < '%s'",
    MAIN_DB_PREFIX,
    "autopro_repair",
    (int) $statusDraft,
    $db->idate($limitDate)
);

$execute = $db->query($delete);

if (!$execute) {
    echo "ERROR: " . $db->lasterror() . "\n";
    exit(1);
}

echo "Cleanup done: " . $db->affected_rows() . " rows deleted\n";
exit(0);
