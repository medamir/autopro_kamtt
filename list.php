<?php

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/autopro/class/repair.class.php';

if (!$user->rights->autopro->config->read) accessforbidden();

$repair = new Repair($db);

$page = GETPOST('page', 'int') ?: 1;
$limit = GETPOST('limit', 'int') ?: 10;
$oldLimit = $limit;

$pagination = $repair->fetchAll($limit, ($page - 1) * $limit);
$total = (int) ($pagination['total'] ?? 0);
$nbpages = ($limit > 0) ? ceil($total / $limit) : 1;

llxHeader();

print_barre_liste(
    "Ordres de réparation",
    $page,
    $_SERVER["PHP_SELF"],
    "",
    '',
    '',
    '',
    -1,
    $total,
    'tools',
    0,
    '',
    '',
    $limit,
    0
);

$fields = [
    ['key' => 'rowid', 'label' => 'ID'],
    ['key' => 'ref', 'label' => 'Référence'],
    ['key' => 'label', 'label' => 'Label'],
    ['key' => 'registration_number', 'label' => 'Immatriculation'],
    ['key' => 'brand', 'label' => 'Marque'],
    ['key' => 'fee', 'label' => 'Tarif'],
    ['key' => 'kilometrage', 'label' => 'Kilométrage'],
    ['key' => 'dates', 'label' => 'Délai'],
    ['key' => 'status', 'label' => 'Status'],
    ['key' => 'actions', 'label' => '']
];

$html = '<div class="div-table-responsive-no-min"><table class="noborder centpercent">';
$html .= '<thead><tr  class="liste_titre">';

foreach ($fields as $field) {
    if ($field['key'] === 'actions' && !$user->rights->autopro->config->write) {
        continue;
    } else {
        $html .= '<th>' . $field['label'] . '</th>';
    }
}

$html .= '</tr></thead>';
$html .= '<tbody>';
foreach ($pagination['data'] as $item) {
    $html .= '<tr>';
    foreach ($fields as $field) {
        $key = $field['key'];

        if ($key  === 'dates') {
            $value = sprintf(
                '<div style="white-space: nowrap;"><div>Dépôt : %s</div><div>Retour prévu : %s</div></div>',
                $item->delivery_date ? dol_print_date($item->delivery_date, 'dayhour') : 'Non défini',
                $item->expected_return_date ? dol_print_date($item->expected_return_date, 'dayhour') : 'Non défini'
            );
        } else  if ($key === 'status') {
            $value =  $item->$key == 1 ? '<span class="badge badge-success">Validé</span>' : '<span class="badge badge-warning">Brouillon</span>';
        } else if ($key === 'actions') {
            if ($user->rights->autopro->config->write) {
                $value = sprintf('<a href="card.php?id=%d">Modifier</a>', $item->rowid);
            } else {
                continue;
            }
        } else if (!empty($item->$key)) {
            $value = htmlspecialchars($item->$key);
        } else {
            $value = "Non défini";
        }

        $html .= '<td>' . $value . '</td>';
    }
    $html .= '</tr>';
}
$html .= '</tbody></table></div>';
print($html);

llxFooter();
