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

    /**
     * Create a new repair record
     */
    public function create($user, $notrigger = 0)
    {
        if (!$this->validateFields()) {
            return -1;
        }

        $sql = sprintf(
            "INSERT INTO %s%s
    (ref, label, registration_number, brand_id, kilometrage, delivery_date, expected_return_date, status, fee)
    VALUES ('%s','%s','%s',%d,%d,%s,%s,%d,%f)",
            MAIN_DB_PREFIX,
            $this->table_element,
            $this->db->escape($this->ref),
            $this->db->escape($this->label),
            $this->db->escape($this->registration_number),
            $this->brand_id ?? null,
            $this->kilometrage ?? null,
            ($this->delivery_date ? "'" . $this->db->idate($this->delivery_date) . "'" : "NULL"),
            ($this->expected_return_date ? "'" . $this->db->idate($this->expected_return_date) . "'" : "NULL"),
            (int) ($this->status ?? self::STATUS_DRAFT),
            (float) ($this->fee ?? 0)
        );

        if ($this->db->query($sql)) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

            if (!empty($this->fk_propal)) {
                $this->add_object_linked('propal', $this->fk_propal);
            }

            return $this->id;
        }

        $this->error = $this->db->lasterror();
        return -1;
    }

    /**
     * Fetch a repair by ID and populate object properties
     */
    public function fetch($id)
    {
        $sql = sprintf(
            "SELECT * FROM %s%s WHERE rowid=%d",
            MAIN_DB_PREFIX,
            $this->table_element,
            (int) $id
        );

        $res = $this->db->query($sql);

        if ($res && ($obj = $this->db->fetch_object($res))) {
            $this->id = $obj->rowid;
            $this->ref = $obj->ref;
            $this->label = $obj->label;
            $this->registration_number = $obj->registration_number;
            $this->brand_id = $obj->brand_id;
            $this->kilometrage = $obj->kilometrage;
            $this->delivery_date = $this->db->jdate($obj->delivery_date);
            $this->expected_return_date = $this->db->jdate($obj->expected_return_date);
            $this->status = $obj->status;
            $this->fee = $obj->fee;

            $this->fetchObjectLinked();
            $ids = $this->linkedObjectsIds;
            if (is_array($ids) && isset($ids['propal'])) {
                $this->fk_propal = reset($ids['propal']);
            }

            return 1;
        }

        return 0;
    }

    /**
     * Update an existing repair record with current object properties
     */
    public function update($user)
    {
        if (!$this->validateFields()) {
            return -1;
        }

        $delivery = $this->delivery_date
            ? "'" . $this->db->idate($this->delivery_date) . "'"
            : "NULL";

        $expected = $this->expected_return_date
            ? "'" . $this->db->idate($this->expected_return_date) . "'"
            : "NULL";

        $sql = sprintf(
            "UPDATE %s%s SET
            ref='%s',
            label='%s',
            registration_number='%s',
            brand_id=%d,
            kilometrage=%d,
            delivery_date=%s,
            expected_return_date=%s,
            status=%d,
            fee=%f
        WHERE rowid=%d",
            MAIN_DB_PREFIX,
            $this->table_element,
            $this->db->escape($this->ref),
            $this->db->escape($this->label),
            $this->db->escape($this->registration_number),
            $this->brand_id ?? null,
            $this->kilometrage ?? null,
            $delivery,
            $expected,
            (int) ($this->status ?? self::STATUS_DRAFT),
            (float) ($this->fee ?? 0),
            (int) $this->id
        );

        if (!$this->db->query($sql)) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        return 1;
    }

    /**
     * Delete a repair record
     */
    public function delete($user, $trigger = true)
    {
        global $conf, $langs;

        $error = 0;

        $this->db->begin();

        $result = $this->fetch($this->id);
        if ($result <= 0) {
            $this->db->rollback();
            return -1;
        }

        if ($trigger) {
            $resTrigger = $this->call_trigger('AUTOPRO_REPAIR_DELETE', $user, $langs, $conf);

            if ($resTrigger < 0) {
                $this->db->rollback();
                return -1;
            }
        }

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "element_element
            WHERE (fk_source = " . (int) $this->id . " AND sourcetype = 'autopro_repair')
               OR (fk_target = " . (int) $this->id . " AND targettype = 'autopro_repair')";

        if (!$this->db->query($sql)) {
            $error++;
        }

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element . "
            WHERE rowid = " . (int) $this->id;

        if (!$this->db->query($sql)) {
            $error++;
        }

        if ($error) {
            $this->db->rollback();
            return -1;
        }

        $this->db->commit();

        return 1;
    }



    /**
     * Count total repairs based on filters for pagination purposes
     */
    public function count($filters = [])
    {
        $sql = "SELECT COUNT(r.rowid) as nb
            FROM " . MAIN_DB_PREFIX . "autopro_repair as r
            LEFT JOIN " . MAIN_DB_PREFIX . "c_autopro_brand as b ON r.brand_id = b.rowid";

        $filtersSql = $this->buildFilters($filters);
        if (!empty($filtersSql)) {
            $sql .= " WHERE " . $filtersSql;
        }

        $resql = $this->db->query($sql);

        if ($resql && ($obj = $this->db->fetch_object($resql))) {
            return (int) $obj->nb;
        }

        return 0;
    }

    /**
     * Fetch all repairs with pagination, filtering, and sorting
     */
    public function fetchAll($limit, $offset, $filters = [], $sortfield = 'r.rowid', $sortorder = 'DESC')
    {
        $sql = "SELECT r.rowid, r.ref, r.label, r.registration_number, r.kilometrage,
                   r.delivery_date, r.expected_return_date, r.status, r.fee,
                   b.label as brand
            FROM " . MAIN_DB_PREFIX . "autopro_repair as r
            LEFT JOIN " . MAIN_DB_PREFIX . "c_autopro_brand as b ON r.brand_id = b.rowid";

        $filtersSql = $this->buildFilters($filters);
        if (!empty($filtersSql)) {
            $sql .= " WHERE " . $filtersSql;
        }

        $sql .= sprintf(
            " ORDER BY %s %s LIMIT %d OFFSET %d",
            $this->db->escape($sortfield),
            $this->db->escape($sortorder),
            (int) $limit,
            (int) $offset
        );

        $resql = $this->db->query($sql);

        $rows = [];
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $rows[] = $obj;
            }
        }

        return $rows;
    }

    /**
     * Build SQL filters based on provided filter values
     */
    private function buildFilters($filters)
    {
        $sql = '';

        if (!empty($filters['ref'])) {
            $sql .= " AND r.ref LIKE '%" . $this->db->escape($filters['ref']) . "%'";
        }

        if (!empty($filters['label'])) {
            $sql .= " AND r.label LIKE '%" . $this->db->escape($filters['label']) . "%'";
        }

        if (!empty($filters['registration_number'])) {
            $sql .= " AND r.registration_number LIKE '%" . $this->db->escape($filters['registration_number']) . "%'";
        }

        if (!empty($filters['brand'])) {
            $sql .= " AND b.label LIKE '%" . $this->db->escape($filters['brand']) . "%'";
        }

        if (!empty($filters['status'])) {
            $sql .= " AND r.status = " . ((int) $filters['status']);
        }

        if (!empty($filters['fee'])) {
            $sql .= " AND r.fee = " . ((float) $filters['fee']);
        }

        if (!empty($filters['kilometrage'])) {
            $sql .= " AND r.kilometrage = " . ((int) $filters['kilometrage']);
        }

        $sql = ltrim($sql, ' AND'); // Remove leading AND
        $sql = trim($sql);

        return $sql;
    }


    /**
     * Validate the fields before saving
     */
    private function validateFields()
    {
        if (strlen($this->registration_number) != 9) {
            $this->error = "Immatriculation doit comporter 9 caractères";
            return false;
        }

        return true;
    }
}
