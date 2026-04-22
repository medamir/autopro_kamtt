<?php

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modAutopro extends DolibarrModules
{
    public function __construct($db)
    {
        global $conf;

        $this->db = $db;

        $this->numero = 99900;
        $this->rights_class = 'autopro';

        $this->family = "garage";
        $this->name = "autopro";
        $this->description = "Gestion des ordres de réparation";
        $this->version = '0.8.0';

        $this->const_name = 'MAIN_MODULE_AUTOPRO';
        $this->picto = 'tools';

        $this->module_parts = [
            'hooks' => ['propalcard'],
            'triggers' => 1,
            'api' => 1,
            'sql' => 1,
        ];

        $this->api = [
            'reparations' => [
                'file' => 'autopro/class/api_autopro.class.php',
                'class' => 'Reparations',
            ],
        ];

        $this->dirs = ['/autopro/sql'];

        $this->dictionaries = [
            'langs' => 'autopro@autopro',
            'tabname' => [MAIN_DB_PREFIX . 'c_autopro_brand'],
            'tablib' => ['AutoPro Marques'],
            'tabsql' => [
                "SELECT rowid, label, fee, active FROM " . MAIN_DB_PREFIX . "c_autopro_brand"
            ],
            'tabsqlsort' => ['label ASC'],
            'tabfield' => ['label,fee'],
            'tabfieldvalue' => ['label,fee'],
            'tabfieldinsert' => ['label,fee'],
            'tabrowid' => ['rowid'],
            'tabcond' => [$conf->autopro->enabled],
        ];

        $this->const = [
            [
                'AUTOPRO_DEFAULT_HOURLY_RATE',
                'chaine',
                '80',
                'Tarif horaire global par défaut',
                0
            ],
        ];

        $this->initRights();
        $this->initMenu();
    }

    public function init($options = '')
    {
        global $db;

        $result = $this->_init([], $options);
        if ($result < 0) return -1;

        $this->runInstall($db);

        return 1;
    }

    public function remove($options = '')
    {
        global $db;

        $db->query("DROP TABLE IF EXISTS " . MAIN_DB_PREFIX . "autopro_repair");
        $db->query("DROP TABLE IF EXISTS " . MAIN_DB_PREFIX . "c_autopro_brand");

        return $this->_remove([], $options);
    }

    private function runInstall($db)
    {
        $file = DOL_DOCUMENT_ROOT . '/custom/autopro/sql/llx_autopro_repair.sql';

        if (file_exists($file)) {
            $sql = file_get_contents($file);
            $queries = array_filter(array_map('trim', explode(';', $sql)));

            foreach ($queries as $q) {
                if ($q) $db->query($q);
            }
        }

        $file = DOL_DOCUMENT_ROOT . '/custom/autopro/json/brands.json';

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);

            if (is_array($data)) {
                foreach ($data as $brand) {
                    $label = $db->escape($brand['name']);
                    $fee = (float) ($brand['fee'] ?? 0);

                    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "c_autopro_brand WHERE label='" . $label . "'";
                    $res = $db->query($sql);

                    if ($res && $db->num_rows($res) == 0) {
                        $db->query("INSERT INTO " . MAIN_DB_PREFIX . "c_autopro_brand (label, fee, active)
                                    VALUES ('" . $label . "', " . $fee . ", 1)");
                    }
                }
            }
        }

        $db->query("DELETE FROM " . MAIN_DB_PREFIX . "extrafields
                    WHERE elementtype='propal'
                    AND name='observations_technicien'");

        $db->query("INSERT INTO " . MAIN_DB_PREFIX . "extrafields
                    (elementtype,label,name,type,size,entity,enabled)
                    VALUES
                    ('propal','Observations technicien','observations_technicien','text','10',1,1)");
    }

    private function initRights()
    {
        $this->rights = [
            [$this->numero + 1, 'Réparations', 'r', 0, 'config', 'read'],
            [$this->numero + 2, 'Créer/Modifier une réparation', 'w', 0, 'config', 'write'],
            [$this->numero + 3, 'Supprimer une réparation', 'd', 0, 'config', 'delete'],
        ];
    }

    private function initMenu()
    {
        $this->menu = [
            [
                'fk_menu' => 0,
                'type' => 'top',
                'titre' => 'Auto Pro',
                'mainmenu' => 'autopro',
                'leftmenu' => 'autopro',
                'url' => '/custom/autopro/list.php',
                'langs' => 'autopro@autopro',
                'position' => 100,
                'enabled' => '$conf->autopro->enabled',
                'perms' => '$user->rights->autopro->config->read',
                'user' => 2,
                'picto' => 'tools',
            ],
            [
                'fk_menu' => 'fk_mainmenu=home,fk_leftmenu=setup',
                'type' => 'left',
                'titre' => 'AutoPro Setup',
                'url' => '/autopro/admin/setup.php',
                'langs' => 'autopro@autopro',
                'position' => 100,
                'enabled' => '$conf->autopro->enabled',
                'perms' => '$user->admin',
                'user' => 2,
            ],
            [
                'fk_menu' => 'fk_mainmenu=autopro',
                'type' => 'left',
                'titre' => 'Liste réparations',
                'mainmenu' => 'autopro',
                'leftmenu' => 'autopro_list',
                'url' => '/custom/autopro/list.php',
                'langs' => 'autopro@autopro',
                'position' => 100,
                'enabled' => '$conf->autopro->enabled',
                'perms' => '$user->rights->autopro->config->read',
                'picto' => 'list',
            ],
            [
                'fk_menu' => 'fk_mainmenu=autopro',
                'type' => 'left',
                'titre' => 'Créer réparation',
                'mainmenu' => 'autopro',
                'leftmenu' => 'autopro_create',
                'url' => '/custom/autopro/card.php?action=create',
                'langs' => 'autopro@autopro',
                'position' => 101,
                'enabled' => '$conf->autopro->enabled',
                'perms' => '$user->rights->autopro->config->write',
                'picto' => 'add',
            ],
        ];
    }
}
