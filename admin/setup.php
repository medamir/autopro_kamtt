<?php

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

global $db, $conf, $langs, $user;

$langs->load("admin");
$langs->load("autopro@autopro");

if (!$user->admin) {
    accessforbidden();
}

$action = GETPOST('action', 'alpha');

if ($action == 'save') {
    $rate = GETPOST('default_rate', 'alpha');

    dolibarr_set_const(
        $db,
        'AUTOPRO_DEFAULT_HOURLY_RATE',
        $rate,
        'chaine',
        0,
        '',
        $conf->entity
    );

    setEventMessages(
        $langs->trans("AutoProConfigSaved"),
        null,
        'mesgs'
    );
}

llxHeader('', $langs->trans("AutoProSetup"));

print load_fiche_titre($langs->trans("AutoProConfiguration"));

print '<form method="POST">';
print '<input type="hidden" name="action" value="save">';

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Setting") . '</td>';
print '<td>' . $langs->trans("Value") . '</td>';
print '</tr>';

print '<tr>';
print '<td>';
print $langs->trans("DefaultHourlyRate");
print info_admin($langs->trans("DefaultHourlyRateHelp"));
print '</td>';

print '<td>';
print '<input type="text" name="default_rate" value="' . getDolGlobalString('AUTOPRO_DEFAULT_HOURLY_RATE') . '">';
print '</td>';
print '</tr>';

print '</table>';

print '<div class="center">';

print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
print '</div>';

print '</form>';

llxFooter();
