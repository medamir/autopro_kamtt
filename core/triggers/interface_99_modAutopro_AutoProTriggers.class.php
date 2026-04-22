<?php

class InterfaceAutoProTriggers
{
    public $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function runTrigger($action, $object, $user, $langs, $conf)
    {
        if (!in_array($action, array('PROPAL_DELETE', 'AUTOPRO_REPAIR_DELETE'))) {
            return 0;
        }

        // PROPAL deleted → delete repair
        if ($action == 'PROPAL_DELETE') {

            $this->db->begin();

            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "autopro_repair
                    WHERE fk_propal = " . ((int) $object->id);

            $res = $this->db->query($sql);

            if (!$res) {
                $this->db->rollback();
                return -1;
            }

            $this->db->commit();
        }

        return 0;
    }
}
