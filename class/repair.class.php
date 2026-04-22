<?php

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

class Repair extends CommonObject
{
    public $element = 'autopro_repair';
    public $table_element = 'autopro_repair';

    public $id;

    public $ref;
    public $label;
    public $registration_number;
    public $brand_id;
    public $kilometrage;
    public $delivery_date;
    public $expected_return_date;
    public $status;
    public $fee;
    public $fk_propal;

    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($user, $notrigger = 0)
    {
        global $db;

        if (!$this->validateFields()) {
            return -1;
        }

        $insert = sprintf(
            "INSERT INTO %s%s (ref, label, registration_number, brand_id, kilometrage, delivery_date, expected_return_date, status, entity, fee) 
            VALUES ('%s', '%s','%s','%s', %d, %s, %s, %d, %d, %f)",
            MAIN_DB_PREFIX,
            $this->table_element,
            $db->escape($this->ref),
            $db->escape($this->label),
            $db->escape($this->registration_number),
            $db->escape($this->brand_id),
            (int) $this->kilometrage,
            ($this->delivery_date ? "'" . $db->idate($this->delivery_date) . "'" : "NULL"),
            ($this->expected_return_date ? "'" . $db->idate($this->expected_return_date) . "'" : "NULL"),
            (int) $this->status,
            (int) $this->entity,
            (float) $this->fee
        );

        $execute = $db->query($insert);

        if ($execute) {
            $this->id = $db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

            if (!empty($this->fk_propal)) {
                $this->add_object_linked('propal', $this->fk_propal);
            }

            return $this->id;
        }

        $this->error = $db->lasterror();
        return -1;
    }

    public function fetch($id)
    {
        global $db;

        $query = "SELECT * FROM %s%s WHERE rowid = %d";
        $select = sprintf($query, MAIN_DB_PREFIX, $this->table_element, (int) $id);

        $execute = $db->query($select);

        if ($execute && ($obj = $db->fetch_object($execute))) {

            $this->id = $obj->rowid;
            $this->ref = $obj->ref;
            $this->label = $obj->label;
            $this->registration_number = $obj->registration_number;
            $this->brand_id = $obj->brand_id;
            $this->kilometrage = $obj->kilometrage;
            $this->delivery_date = $db->jdate($obj->delivery_date);
            $this->expected_return_date = $db->jdate($obj->expected_return_date);
            $this->status = $obj->status;
            $this->fee = $obj->fee;

            return 1;
        }

        return 0;
    }

    public function update($user)
    {
        global $db;

        if (!$this->validateFields()) {
            return -1;
        }

        $query = "UPDATE %s%s 
        SET ref = '%s', label = '%s', registration_number = '%s', brand_id = '%s', " .
            "kilometrage = %d, delivery_date = %s, expected_return_date = %s, status = %d, fee = %f
        WHERE rowid = %d";

        $delivery_date = $this->delivery_date ? sprintf("'%s'", $db->idate($this->delivery_date)) : "NULL";
        $expected_return_date = $this->expected_return_date ? sprintf("'%s'", $db->idate($this->expected_return_date)) : "NULL";

        $update = sprintf(
            $query,
            MAIN_DB_PREFIX,
            $this->table_element,
            $db->escape($this->ref),
            $db->escape($this->label),
            $db->escape($this->registration_number),
            $db->escape($this->brand_id),
            (int) $this->kilometrage,
            $delivery_date,
            $expected_return_date,
            (int) $this->status,
            (float) $this->fee,
            (int) $this->id
        );

        $execute = $db->query($update);

        if (!$execute) {
            $this->error = $db->lasterror();
            return -1;
        }

        return 1;
    }


    public function delete($user)
    {
        $this->db->begin();

        $delete = sprintf(
            "DELETE FROM %s%s
                WHERE (fk_source = %d AND sourcetype = 'autopro_repair')
                   OR (fk_target = %d AND targettype = 'autopro_repair')",
            MAIN_DB_PREFIX,
            "element_element",
            (int) $this->id,
            (int) $this->id
        );

        $execute = $this->db->query($delete);

        if (!$execute) {
            $this->db->rollback();
            return -1;
        }

        $delete = sprintf(
            "DELETE FROM %s%s WHERE rowid = %d",
            MAIN_DB_PREFIX,
            $this->table_element,
            (int) $this->id
        );

        $execute = $this->db->query($delete);

        if (!$execute) {
            $this->db->rollback();
            return -1;
        }

        $this->db->commit();
        return 1;
    }

    public function count()
    {
        global $db;

        $query = "SELECT COUNT(rowid) as count FROM %s%s";
        $select = sprintf($query, MAIN_DB_PREFIX, $this->table_element);

        $execute = $db->query($select);

        if ($execute && ($obj = $db->fetch_object($execute))) {
            return (int) $obj->count;
        }

        return 0;
    }

    public function fetchAll($limit = 100, $offset = 0)
    {
        global $db;

        $rows = [];

        $query = "SELECT ro.*, m.label as brand
          FROM %s%s as ro
          JOIN %s%s as m ON ro.brand_id = m.rowid
          ORDER BY ro.rowid DESC
          LIMIT %d OFFSET %d";

        $fetch = sprintf(
            $query,
            MAIN_DB_PREFIX,
            $this->table_element,
            MAIN_DB_PREFIX,
            "c_autopro_brand",
            (int) $limit,
            (int) $offset
        );

        $execute = $db->query($fetch);

        if ($execute) {
            while ($obj = $db->fetch_object($execute)) {
                $rows[] = $obj;
            }
        }

        $npPages = ceil($this->count() / $limit);

        return [
            'data' => $rows,
            'total' => $this->count(),
            'pages' => $npPages,
        ];
    }

    public function getBrands()
    {
        global $db;

        $brands = [];

        $query = "SELECT rowid, label FROM %s%s WHERE active = 1 ORDER BY label ASC";
        $fetch = sprintf($query, MAIN_DB_PREFIX, "c_autopro_brand");

        $execute = $db->query($fetch);

        if ($execute) {
            while ($obj = $db->fetch_object($execute)) {
                $brands[] = $obj;
            }
        }

        return $brands;
    }

    private function validateFields()
    {
        if (strlen($this->registration_number) != 9) {
            $this->error = "Immatriculation doit comporter 9 caractères";
            return false;
        }

        return true;
    }
}
