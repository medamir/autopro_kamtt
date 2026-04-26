<?php

require_once dirname(__DIR__) . '/../../main.inc.php';

global $db;

if (php_sapi_name() !== 'cli') {
    die("CLI only\n");
}

$statusDraft = 0;
$limitDate = dol_time_plus_duree(dol_now(), -3, 'w');

$sql = sprintf(
    "DELETE FROM %s%s
     WHERE status = %d
     AND delivery_date < '%s'",
    MAIN_DB_PREFIX,
    "autopro_repair",
    (int) $statusDraft,
    $db->idate($limitDate)
);

$db->begin();

$res = $db->query($sql);

if (!$res) {
    $db->rollback();
    echo "ERROR: " . $db->lasterror() . "\n";
    exit(1);
}

$deleted = $db->affected_rows($res);

$db->commit();

echo "Cleanup done: " . $deleted . " rows deleted\n";
exit(0);
