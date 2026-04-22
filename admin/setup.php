<?php

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

$langs->load("admin");

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');

if ($action == 'save') {
    $rate = GETPOST('default_rate', 'alpha');
    dolibarr_set_const($db, 'AUTOPRO_DEFAULT_HOURLY_RATE', $rate, 'chaine', 0, '', $conf->entity);
}

llxHeader('', 'AutoPro Setup');

print load_fiche_titre("AutoPro Configuration");

print '<form method="POST">';
print '<input type="hidden" name="action" value="save">';

print '<table class="noborder centpercent">';

print '<tr class="liste_titre"><td>Setting</td><td>Value</td></tr>';

print '<tr>';
print '<td>';
print 'Tarif horaire global par défaut';
print info_admin("Utilisé lorsqu'aucun tarif spécifique à la marque n'est défini.");
print '</td>';
print '<td>';
print '<input type="text" name="default_rate" value="' . getDolGlobalString('AUTOPRO_DEFAULT_HOURLY_RATE') . '">';
print '</td>';
print '</tr>';

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button" value="Save">';
print '</div>';

print '</form>';

llxFooter();
