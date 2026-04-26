<?php

include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modAutopro extends DolibarrModules
{
    public const BRAND_TABLE = MAIN_DB_PREFIX . 'c_autopro_brand';
    public const REPARATION_TABLE = MAIN_DB_PREFIX . 'autopro_repair';

    /**
     * Constructor
     */
    public function __construct($db)
    {
        global $conf;

        $this->db = $db;

        $this->numero = 104500;
        $this->rights_class = 'autopro';

        $this->family = "other";
        $this->name = "autopro";
        $this->description = "ModDescription";
        $this->version = '1.0.0';

        $this->langfiles = ["autopro@autopro"];

        $this->const_name = 'MAIN_MODULE_AUTOPRO';
        $this->picto = 'tools';

        $this->module_parts = [
            'hooks' => ['propalcard'],
            'triggers' => 1,
            'api' => 1,
        ];

        $this->config_page_url = ['setup.php@autopro'];

        $this->initRights();
        $this->initDictionaries();
        $this->initConstants();
        $this->initMenu();
    }

    /**
     * Module init
     */
    public function init($options = '')
    {
        $sqlFiles = glob(sprintf('%s/custom/autopro/sql/*.sql', DOL_DOCUMENT_ROOT));

        $queries = [];

        if (is_array($sqlFiles)) {
            foreach ($sqlFiles as $file) {
                $sql = $this->getqueryFromFile($file);

                if ($sql) {
                    $queries[] = $sql;
                }
            }
        }

        $result = $this->_init($queries, $options);

        if ($result < 0) {
            return -1;
        }

        $this->createExtraFields();
        $this->seedBrandQueries();

        return 1;
    }

    /**
     * Read SQL file content
     */
    private function getqueryFromFile($filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        return file_get_contents($filePath);
    }

    /**
     * Seed brands from JSON
     */
    private function seedBrandQueries()
    {
        $file = sprintf('%s/custom/autopro/json/brands.json', DOL_DOCUMENT_ROOT);

        if (!file_exists($file)) {
            return;
        }

        $data = json_decode(file_get_contents($file), true);

        if (!is_array($data)) {
            return;
        }

        foreach ($data as $brand) {
            $label = $this->db->escape($brand['name']);
            $fee = (float) ($brand['fee'] ?? 0);

            $res = $this->db->query(
                sprintf(
                    "SELECT rowid FROM %s WHERE label='%s'",
                    self::BRAND_TABLE,
                    $label
                )
            );

            if ($res && $this->db->num_rows($res) == 0) {
                $this->db->query(
                    sprintf(
                        "INSERT INTO %s (label, fee, active) VALUES ('%s', %f, 1)",
                        self::BRAND_TABLE,
                        $label,
                        $fee
                    )
                );
            }
        }
    }

    /**
     * Dictionaries definition
     */
    private function initDictionaries()
    {
        $this->dictionaries = [
            'langs' => 'autopro@autopro',
            'tabname' => [self::BRAND_TABLE],
            'tablib' => ['AutoProBrands'],
            'tabsql' => [
                sprintf(
                    "SELECT rowid, label as BrandLabel, fee as BrandFee FROM %s as brand",
                    self::BRAND_TABLE
                )
            ],
            'tabsqlsort' => ['label ASC'],
            'tabfield' => ['BrandLabel,BrandFee'],
            'tabfieldvalue' => ['BrandLabel,BrandFee'],
            'tabfieldinsert' => ['BrandLabel,BrandFee'],
            'tabrowid' => ['rowid'],
            'tabcond' => [isModEnabled('autopro')],
        ];
    }

    /**
     * Constants definition
     */
    private function initConstants()
    {
        $this->const = [
            ['AUTOPRO_DEFAULT_HOURLY_RATE', 'chaine', '80', 'DefaultFee', 0],
        ];
    }

    /**
     * Extra fields creation
     */
    private function createExtraFields()
    {
        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

        $extrafields = new ExtraFields($this->db);

        $extrafields->addExtraField(
            'observations_technicien',
            'AutoproObservationsTechnicien',
            'text',
            0,
            '',
            'propal'
        );
    }

    /**
     * Rights definition
     */
    private function initRights()
    {
        $this->rights = [
            [$this->numero + 1, 'RightsAutoProRead', 'r', 1, 'main', 'read'],
            [$this->numero + 2, 'RightsAutoProWrite', 'w', 0, 'main', 'write'],
            [$this->numero + 3, 'RightsAutoProDelete', 'd', 0, 'main', 'delete'],
            [$this->numero + 5, 'RightsAutoProConfig', 'w', 0, 'config', 'write'],
        ];
    }

    /**
     * Menu definition
     */
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
                'perms' => '$user->rights->autopro->main->read',
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
                'perms' => '$user->rights->autopro->config->write',
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
                'perms' => '$user->rights->autopro->main->read',
                'picto' => 'list',
            ],
            [
                'fk_menu' => 'fk_mainmenu=autopro',
                'type' => 'left',
                'titre' => 'Créer réparation',
                'mainmenu' => 'autopro',
                'leftmenu' => 'autopro_create',
                'url' => '/custom/autopro/card.php',
                'langs' => 'autopro@autopro',
                'position' => 101,
                'enabled' => '$conf->autopro->enabled',
                'perms' => '$user->rights->autopro->main->write',
                'picto' => 'add',
            ],
        ];
    }
}
