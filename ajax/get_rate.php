<?php

require '../../../main.inc.php';

if (!$user->rights->autopro->config->write) accessforbidden();

$brand_id = GETPOST('brand_id', 'int');

if ($brand_id > 0) {
    $sql = "SELECT fee FROM " . MAIN_DB_PREFIX . "c_autopro_brand WHERE rowid = " . $brand_id;
    $res = $db->query($sql);

    if ($res && $obj = $db->fetch_object($res)) {
        echo json_encode([
            'fee' => (float) $obj->fee
        ]);
        exit;
    }
}

echo json_encode([
    'fee' => (float) getDolGlobalString('AUTOPRO_DEFAULT_HOURLY_RATE')
]);
