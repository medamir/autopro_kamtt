<?php

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/custom/autopro/class/repair.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/autopro/class/brand.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

global $db, $user, $langs;

$langs->load("main");
$langs->load("autopro@autopro");

if (!$user->rights->autopro->config->write) {
    accessforbidden();
}

$form   = new Form($db);
$action = GETPOST('action', 'alpha');
$id     = GETPOST('id', 'int');

$object = new Repair($db);
$propal = null;

/**
 * Load existing object
 */
if ($id > 0) {
    $object->fetch($id);

    if (!$object->id) {
        setEventMessages($langs->trans("AutoproRepairNotFound"), null, 'errors');
        header("Location: list.php");
        exit;
    }

    if ($object->fk_propal) {
        $propal = new Propal($db);
        $propal->fetch($object->fk_propal);
    }
} else {
    $fk_propal = GETPOST('fk_propal', 'int');

    if ($fk_propal > 0) {
        require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

        $propal = new Propal($db);
        $propal->fetch($fk_propal);

        $object->ref       = 'R-' . $propal->ref;
        $object->label     = $langs->trans("AutoproRepairForProposal", $propal->ref);
        $object->fk_propal = $fk_propal;
    }
}

if ($propal !== null && !$propal->id) {
    setEventMessages($langs->trans("AutoproRepairPropalNotFound"), null, 'errors');
    header("Location: list.php");
    exit;
}
/**
 * Delete
 */
if ($action === $langs->trans("Delete") && $id > 0) {
    $res = $object->delete($user);

    if ($res > 0) {
        setEventMessages($langs->trans("AutoproRepairDeleted"), null, 'mesgs');
        header("Location: list.php");
        exit;
    } else {
        setEventMessages($object->error, null, 'errors');
    }
}

/**
 * Save
 */
elseif ($action === $langs->trans("Save")) {
    $object->ref                  = GETPOST('ref', 'alpha');
    $object->label                = GETPOST('label', 'alpha');
    $object->registration_number  = GETPOST('registration_number', 'alpha');
    $object->brand_id             = GETPOST('brand_id', 'alpha');
    $object->fee                  = (float) GETPOST('fee', 'alpha');
    $object->kilometrage          = GETPOST('kilometrage', 'int');
    $object->status               = GETPOST('status', 'int');
    $object->fk_propal            = GETPOST('fk_propal', 'int');

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

    $res = ($id > 0)
        ? $object->update($user)
        : $object->create($user);

    if ($res > 0) {
        setEventMessages(
            $id > 0 ? $langs->trans("AutoproRepairUpdated") : $langs->trans("AutoproRepairCreated"),
            null,
            'mesgs'
        );

        header("Location: card.php?id=" . $object->id);
        exit;
    } else {
        setEventMessages($object->error, null, 'errors');
    }
}

/**
 * Page title
 */
$title = ($id > 0)
    ? $langs->trans("AutoproRepairEdit")
    : $langs->trans("AutoproRepairCreate");

llxHeader('', $title);

print '<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />';

print '<div class="fiche">';
print '<div class="fichecenter">';

print '<form method="POST">';
print '<input type="hidden" name="token" value="' . newToken() . '">';

print '<table class="border centpercent">';

if ($propal) {
    print '<tr><td>' . $langs->trans("LinkedProposal") . '</td><td>';
    print '<a href="' . DOL_URL_ROOT . '/comm/propal/card.php?id=' . $propal->id . '">';
    print $propal->ref;
    print '</a>';
    print '</td></tr>';
}
/**
 * Fields
 */
print '<tr><td class="titlefield">' . $langs->trans("Ref") . '</td>';
print '<td><input type="text" name="ref" value="' . dol_escape_htmltag($object->ref) . '"></td></tr>';

print '<tr><td>' . $langs->trans("Label") . '</td>';
print '<td><input type="text" name="label" value="' . dol_escape_htmltag($object->label) . '"></td></tr>';

print '<tr><td>' . $langs->trans("RegistrationNumber") . '</td>';
print '<td><input type="text" name="registration_number" value="' . dol_escape_htmltag($object->registration_number) . '"></td></tr>';

print '<tr><td>' . $langs->trans("Mileage") . '</td>';
print '<td><input type="number" name="kilometrage" value="' . dol_escape_htmltag($object->kilometrage) . '"></td></tr>';

print '<tr><td>' . $langs->trans("Brand") . '</td><td>';

$brands = (new Brand($db))->fetchAll();

print '<select name="brand_id" id="brand_id" style="width:300px">';
print '<option value=""></option>';
print '<option value="0">' . $langs->trans("NoBrandDefined") . '</option>';

foreach ($brands as $brand) {
    $selected = "";
    if ($id > 0) {
        $selected = ((int) $object->brand_id === (int) $brand->rowid) ? 'selected' : '';
    } else {
        $object->fee = (float) getDolGlobalString('AUTOPRO_DEFAULT_HOURLY_RATE');
    }

    print '<option value="' . dol_escape_htmltag($brand->rowid) . '" ' . $selected . '>';
    print dol_escape_htmltag($brand->rowid) . " # " . dol_escape_htmltag($brand->label);
    print '</option>';
}

print '</select>';
print '</td></tr>';

print '<tr><td>' . $langs->trans("Fee") . '</td>';
print '<td><input type="number" step="0.01" name="fee" value="' . dol_escape_htmltag($object->fee) . '"></td></tr>';

print '<tr><td>' . $langs->trans("DeliveryDate") . '</td><td>';
print $form->selectDate($object->delivery_date, 'delivery_date', 1, 1, 0, '', 1, 0);
print '</td></tr>';

print '<tr><td>' . $langs->trans("ExpectedReturnDate") . '</td><td>';
print $form->selectDate($object->expected_return_date, 'return_date', 1, 1, 0, '', 1, 0);
print '</td></tr>';

print '<tr><td>' . $langs->trans("Status") . '</td><td>';
print '<select name="status">';
print '<option value="0" ' . ($object->status == 0 ? 'selected' : '') . '>' . $langs->trans("Draft") . '</option>';
print '<option value="1" ' . ($object->status == 1 ? 'selected' : '') . '>' . $langs->trans("Validated") . '</option>';
print '</select>';
print '</td></tr>';

print '</table>';

/**
 * Actions
 */
print '<div class="center">';

print '<a href="list.php">' . $langs->trans("BackToList") . '</a> ';

print '<input type="submit" class="button button-save" value="' . $langs->trans("Save") . '" name="action">';

if ($id > 0) {
    print '<input type="submit" class="button" value="' . $langs->trans("Delete") . '" name="action" ';
    print 'onclick="return confirm(\'' . $langs->trans("ConfirmDeleteRepair") . '\');">';
}

print '</div>';

print '</form>';

print '</div>';
print '</div>';

/**
 * Scripts
 */
print '<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>';

print '<script>
$(function() {
    $("#brand_id").select2({
        placeholder: "",
        allowClear: false
    });
});
</script>';

if (empty($id)) {
    print '<script src="/custom/autopro/assets/js/create.js"></script>';
}

llxFooter();
