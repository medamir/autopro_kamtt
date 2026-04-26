<?php

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class Brand extends CommonObject
{
    public $element       = 'c_autopro_brand';
    public $table_element = 'c_autopro_brand';

    public $id;
    public $label;
    public $fee;
    public $active;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get all active brands
     *
     * @return array
     */
    public function fetchAll()
    {
        $sql = sprintf(
            "SELECT rowid, label, fee, active
             FROM %s
             WHERE active = 1
             ORDER BY label ASC",
            MAIN_DB_PREFIX . $this->table_element
        );

        $resql  = $this->db->query($sql);
        $brands = [];

        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $brands[] = $obj;
            }
        }

        return $brands;
    }
}
