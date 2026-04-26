<?php

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT . '/custom/autopro/class/repair.class.php';

class Autopro
{
    /**
     * @url GET /reparations
     */
    public function getReparations()
    {
        global $db, $user;

        if (!$user->rights->autopro->main->read) {
            throw new RestException(403, "Access denied");
        }

        $limit = GETPOST('limit', 'int') ?: 100;
        $page  = GETPOST('page', 'int') ?: 0;

        $offset = $page * $limit;

        $repair = new Repair($db);

        $rows = $repair->fetchAll($limit, $offset);
        $total = $repair->count();

        return [
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $total,
                'pages' => $limit > 0 ? ceil($total / $limit) : 1
            ],
            'data' => $rows
        ];
    }
}
