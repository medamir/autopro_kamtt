<?php

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

class InterfaceAutoproTriggers extends DolibarrTriggers
{
    private static $inCascade = false;

    public function __construct($db)
    {
        parent::__construct($db);

        $this->family = "autopro";
        $this->description = "AutoPro triggers";
        $this->version = self::VERSIONS['dev'];
    }

    private function logAction($message)
    {
        dol_syslog('[AUTOPRO] ' . $message, LOG_DEBUG);
    }

    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        $this->logAction("=== TRIGGER START ===");
        $this->logAction("Action: $action");

        if (!isModEnabled('autopro')) {
            $this->logAction("Module autopro is NOT enabled → exit");
            return 0;
        }

        $this->logAction("Module autopro is enabled");

        $objectId = isset($object->id) ? $object->id : 'NULL';
        $this->logAction("Object ID: " . $objectId);

        if (isset($object->fk_propal)) {
            $this->logAction("Object fk_propal: " . $object->fk_propal);
        }

        switch ($action) {

            case 'PROPAL_DELETE':
                $this->logAction("Entering onPropalDelete()");
                $res = $this->onPropalDelete($object);
                $this->logAction("Leaving onPropalDelete() with result = $res");
                return $res;

            case 'AUTOPRO_REPAIR_DELETE':
                $this->logAction("Entering onRepairDelete()");
                $res = $this->onRepairDelete($object, $user);
                $this->logAction("Leaving onRepairDelete() with result = $res");
                return $res;

            default:
                $this->logAction("No handler for action: $action");
                return 0;
        }
    }

    /**
     * When a propal is deleted → delete linked repairs
     */
    private function onPropalDelete($object)
    {
        $this->logAction("onPropalDelete: START");

        if (empty($object->id)) {
            $this->logAction("No propal ID → exit");
            return 0;
        }

        $sql = "SELECT fk_target 
            FROM " . MAIN_DB_PREFIX . "element_element
            WHERE fk_source = " . (int) $object->id . "
            AND sourcetype = 'propal'
            AND targettype = 'autopro_repair'";

        $this->logAction("SQL SELECT: " . $sql);

        $resql = $this->db->query($sql);

        if (!$resql) {
            $this->logAction("SELECT ERROR: " . $this->db->lasterror());
            return -1;
        }

        require_once DOL_DOCUMENT_ROOT . '/custom/autopro/class/repair.class.php';

        while ($obj = $this->db->fetch_object($resql)) {

            $this->logAction("Found repair ID: " . $obj->fk_target);

            $repair = new Repair($this->db);

            if ($repair->fetch($obj->fk_target) > 0) {
                $this->logAction("Deleting repair ID: " . $repair->id);

                $res = $repair->delete($GLOBALS['user'], false);

                if ($res < 0) {
                    $this->logAction("ERROR deleting repair ID: " . $repair->id);
                    return -1;
                }
            }
        }

        $this->logAction("onPropalDelete: DONE");

        return 0;
    }

    /**
     * When a repair is deleted → delete linked propal
     */
    private function onRepairDelete($object, $user)
    {
        $this->logAction("onRepairDelete: START");

        if (empty($object->fk_propal)) {
            $this->logAction("onRepairDelete: fk_propal is empty → exit");
            return 0;
        }

        $this->logAction("Loading propal ID: " . $object->fk_propal);

        require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

        $propal = new Propal($this->db);

        $fetchRes = $propal->fetch($object->fk_propal);

        $this->logAction("Propal fetch result: " . $fetchRes);

        if ($fetchRes > 0) {
            $this->logAction("Deleting propal ID: " . $object->fk_propal);

            $res = $propal->delete($user);

            $this->logAction("Propal delete result: " . $res);

            if ($res < 0) {
                $this->logAction("Propal delete FAILED");
                return -1;
            }
        } else {
            $this->logAction("Propal not found or fetch failed");
        }

        $this->logAction("onRepairDelete: END");
        return 0;
    }
}
