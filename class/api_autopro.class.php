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

        if (!$user->rights->autopro->config->read) {
            throw new RestException(403, "Access denied");
        }

        $limit = GETPOST('limit', 'int') ?: 100;
        $page  = GETPOST('page', 'int') ?: 0;

        $offset = $page * $limit;

        $repair = new Repair($db);

        $pagination = $repair->fetchAll($limit, $offset);

        return [
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $pagination['total'],
                'pages' => $limit > 0 ? ceil($pagination['total'] / $limit) : 1
            ],
            'data' => $pagination['data']
        ];
    }
}
