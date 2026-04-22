<?php

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/autopro/class/repair.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

global $db, $user, $langs;

$langs->load("main");

if (!$user->rights->autopro->config->write) accessforbidden();

$form = new Form($db);

$action = GETPOST('action', 'alpha');
$submit = GETPOST('submit', 'alpha');

$id = GETPOST('id', 'int');

$object = new Repair($db);

if ($id > 0) {
    $object->fetch($id);
    if (!$object->id) {
        setEventMessages("Réparation non trouvée", null, 'errors');
        header("Location: list.php");
        exit;
    }
} else {

    $fk_propal = GETPOST('fk_propal', 'int');

    if ($fk_propal > 0) {

        require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

        $propal = new Propal($db);
        $propal->fetch($fk_propal);

        $object->ref = 'PROP-' . $propal->ref;
        $object->label = 'Réparation pour ' . $propal->ref;
        $object->fk_propal = $fk_propal;
    }
}

if ($action === 'delete' && $id > 0) {
    $res = $object->delete($user);
    if ($res > 0) {
        setEventMessages("Réparation supprimée", null, 'mesgs');
        header("Location: list.php");
        exit;
    } else {
        setEventMessages($object->error, null, 'errors');
    }
} else if ($action === 'save') {
    $object->ref = GETPOST('ref', 'alpha');
    $object->label = GETPOST('label', 'alpha');
    $object->registration_number = GETPOST('registration_number', 'alpha');
    $object->brand_id = GETPOST('brand_id', 'alpha');
    $object->fee = (float)(GETPOST('fee', 'alpha'));
    $object->kilometrage = GETPOST('kilometrage', 'int');
    $object->status = GETPOST('status', 'int');
    $object->fk_propal = GETPOST('fk_propal', 'int');

    $object->delivery_date = dol_mktime(
        GETPOST('delivery_datehour'),
        GETPOST('delivery_datemin'),
        0,
        GETPOST('delivery_datemonth'),
        GETPOST('delivery_dateday'),
        GETPOST('delivery_dateyear')
    );

    $object->expected_return_date = dol_mktime(
        GETPOST('return_datehour'),
        GETPOST('return_datemin'),
        0,
        GETPOST('return_datemonth'),
        GETPOST('return_dateday'),
        GETPOST('return_dateyear')
    );

    if ($id > 0) {
        $res = $object->update($user);
    } else {
        $res = $object->create($user);
    }

    if ($res > 0) {
        setEventMessages($id > 0 ? "Réparation mise à jour" : "Réparation créée", null, 'mesgs');
        header("Location: card.php?id=" . $object->id);
        exit;
    } else {
        setEventMessages($object->error, null, 'errors');
    }
}

llxHeader('', 'Repair Card');

print '<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />';

print '<div class="fiche">';

print '<div class="fichecenter">';

print '<form method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';

print '<table class="border centpercent">';

print '<tr><td class="titlefield">Référence</td>';
print '<td><input type="text" name="ref" value="' . dol_escape_htmltag($object->ref) . '"></td></tr>';

print '<tr><td>Label</td>';
print '<td><input type="text" name="label" value="' . dol_escape_htmltag($object->label) . '"></td></tr>';

print '<tr><td>Immatriculation</td>';
print '<td><input type="text" name="registration_number" value="' . dol_escape_htmltag($object->registration_number) . '"></td></tr>';

print '<tr><td>Kilométrage</td>';
print '<td><input type="number" name="kilometrage" value="' . dol_escape_htmltag($object->kilometrage) . '"></td></tr>';

print '<tr><td>Marque</td><td>';

$brands = $object->getBrands();

print '<select name="brand_id" id="brand_id" style="width:300px">';

foreach ($brands as $brand) {
    if ($id > 0) {
        $selected = ((int)$object->brand_id === (int)$brand->rowid) ? 'selected' : '';
    } else {
        $selected = ((int)$brand->rowid === 1) ? 'selected' : '';
        $object->fee = (float)($brand->fee ?? getDolGlobalString('AUTOPRO_DEFAULT_HOURLY_RATE'));
    }

    print '<option value="' . dol_escape_htmltag($brand->rowid) . '" ' . $selected . '>' .
        dol_escape_htmltag($brand->rowid) . " # " . dol_escape_htmltag($brand->label) .
        '</option>';
}

print '</select>';

print '</td></tr>';

print '<tr><td>Tarif</td>';
print '<td><input type="number" name="fee" value="' . dol_escape_htmltag($object->fee) . '" step="0.01"></td></tr>';

print '<tr><td>Date dépôt</td><td>';
print $form->selectDate($object->delivery_date, 'delivery_date', 1, 1, 0, '', 1, 0);
print '</td></tr>';

print '<tr><td>Date restitution prévue</td><td>';
print $form->selectDate($object->expected_return_date, 'return_date', 1, 1, 0, '', 1, 0);
print '</td></tr>';

print '<tr><td>Status</td><td>';
print '<select name="status">';
print '<option value="0" ' . ($object->status == 0 ? 'selected' : '') . '>Brouillon</option>';
print '<option value="1" ' . ($object->status == 1 ? 'selected' : '') . '>Validé</option>';
print '</select>';
print '</td></tr>';

print '</table>';

print '<div class="center">';
print '<a href="list.php" class="">Retour à la liste</a>';
print '<input type="submit" class="button button-save" value="save" name="action">';

if ($id > 0) {
    print '<input type="submit" class="button" value="delete" ' .
        'onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cette réparation ?\');" name="action">';
}

print '</div>';

print '</form>';

print '</div>';
print '</div>';

print '<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>';

print '<script>
$(function() {
    $("#brand_id").select2({
        placeholder: "Selectionnez une marque",
        width: "100%"
    });
});
</script>';

if (empty($id)) {
    print '<script src="/custom/autopro/assets/js/create.js"></script>';
}

llxFooter();
