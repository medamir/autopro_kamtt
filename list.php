<?php

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/autopro/class/repair.class.php';

$langs->loadLangs(['autopro@autopro']);

if (!$user->rights->autopro->main->read) {
    accessforbidden();
}

$repair = new Repair($db);

$page       = GETPOST('page', 'int');
$limit      = GETPOST('limit', 'int');
$sortfield  = GETPOST('sortfield', 'alpha');
$sortorder  = GETPOST('sortorder', 'alpha');

if (!$sortfield) $sortfield = 'r.rowid';
if (!$sortorder) $sortorder = 'DESC';
if ($limit <= 0) $limit = 10;
if ($page < 0) $page = 0;

$offset = $page * $limit;

$filters = [
    'ref' => GETPOST('search_ref', 'alphanohtml'),
    'label' => GETPOST('search_label', 'alphanohtml'),
    'registration_number' => GETPOST('search_reg', 'alphanohtml'),
    'brand' => GETPOST('search_brand', 'alphanohtml'),
    'status' => GETPOST('search_status', 'int'),
    'fee' => GETPOST('search_fee', 'alpha'),
    'kilometrage' => GETPOST('search_km', 'int'),
];

$total = $repair->count($filters);
$rows  = $repair->fetchAll($limit, $offset, $filters, $sortfield, $sortorder);

$param = '';

foreach ($filters as $k => $v) {
    if ($v !== '' && $v !== null) {
        $param .= '&search_' . $k . '=' . urlencode($v);
    }
}

llxHeader('', $langs->trans("AutoProRepairs"));

print_barre_liste(
    $langs->trans("AutoProRepairs"),
    $page,
    $_SERVER["PHP_SELF"],
    $param,
    $sortfield,
    $sortorder,
    '',
    0,
    $total,
    'tools',
    0,
    '',
    '',
    $limit,
    1
);

print '<form method="GET" action="' . $_SERVER["PHP_SELF"] . '">';

print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';

print_liste_field_titre($langs->trans("Id"), $_SERVER["PHP_SELF"], "r.rowid", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "r.ref", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Label"), $_SERVER["PHP_SELF"], "r.label", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("RegistrationNumber"), $_SERVER["PHP_SELF"], "r.registration_number", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Brand"), $_SERVER["PHP_SELF"], "b.label", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Fee"), $_SERVER["PHP_SELF"], "r.fee", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Kilometrage"), $_SERVER["PHP_SELF"], "r.kilometrage", "", $param, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"], "r.status", "", $param, '', $sortfield, $sortorder);
print '<th></th>';

print '</tr>';

print '<tr class="liste_titre">';

print '<td></td>';

print '<td><input type="text" style="width: 100px;" name="search_ref" value="' . dol_escape_htmltag($filters['ref']) . '"></td>';
print '<td><input type="text" name="search_label" value="' . dol_escape_htmltag($filters['label']) . '"></td>';
print '<td><input type="text" name="search_reg" value="' . dol_escape_htmltag($filters['registration_number']) . '"></td>';
print '<td><input type="text" name="search_brand" value="' . dol_escape_htmltag($filters['brand']) . '"></td>';
print '<td><input type="text" name="search_fee" value="' . dol_escape_htmltag($filters['fee']) . '"></td>';
print '<td><input type="text" name="search_km" value="' . dol_escape_htmltag($filters['kilometrage']) . '"></td>';

print '<td>
    <select name="search_status">
        <option value=""></option>
        <option value="0"' . ($filters['status'] === 0 ? ' selected' : '') . '>' . $langs->trans("Draft") . '</option>
        <option value="1"' . ($filters['status'] == 1 ? ' selected' : '') . '>' . $langs->trans("Validated") . '</option>
    </select>
</td>';

print '<td><button type="submit" class="button">' . $langs->trans("Filter") . '</button></td>';

print '</tr>';

foreach ($rows as $obj) {

    print '<tr class="oddeven">';

    print '<td>' . $obj->rowid . '</td>';
    print '<td>' . htmlspecialchars($obj->ref) . '</td>';
    print '<td>' . htmlspecialchars($obj->label) . '</td>';
    print '<td>' . htmlspecialchars($obj->registration_number) . '</td>';
    print '<td>' . htmlspecialchars($obj->brand) . '</td>';
    print '<td>' . price($obj->fee) . '</td>';
    print '<td>' . (int) $obj->kilometrage . '</td>';

    print '<td>';
    print ($obj->status == 1)
        ? '<span class="badge badge-success">' . $langs->trans("Validated") . '</span>'
        : '<span class="badge badge-warning">' . $langs->trans("Draft") . '</span>';
    print '</td>';

    print '<td>';
    if ($user->rights->autopro->main->write) {
        print '<a href="card.php?id=' . $obj->rowid . '" class="butAction">' . $langs->trans("Modify") . '</a>';
    }
    print '</td>';

    print '</tr>';
}

print '</table>';
print '</div>';

print '</form>';

llxFooter();
