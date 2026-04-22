<?php

class ActionsAutopro
{
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $conf;

        if ($object->element == 'propal' && !empty($conf->autopro->enabled)) {
            print '<a class="butAction" href="' . DOL_URL_ROOT . '/custom/autopro/card.php?fk_propal=' . $object->id . '">';
            print 'Créer ordre de réparation';
            print '</a>';
        }

        return 0;
    }

    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf;

        if ($object->element === 'propal' && !empty($conf->autopro->enabled)) {
            if (empty($object->array_options)) {
                $object->fetch_optionals();
            }
        }

        return 0;
    }
}
