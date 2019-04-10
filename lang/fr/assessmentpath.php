<?php

/* * *************************************************************
 *  This script has been developed for Moodle - http://moodle.org/
 *
 *  You can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
  *
 * ************************************************************* */

/**
 * Strings for component 'assessmentpath', language 'en'
 *
 */

// Plugin strings

$string['assessmentpath'] = 'Assessment Path';
$string['modulename'] = 'Assessment Path';
$string['modulename_help'] = 'L\'activité Assessment Path permet de créer une séquence de tests initiaux et tests de remédiation.';
$string['modulenameplural'] = 'Assessment Paths';
$string['pluginadministration'] = 'Assessment Path';
$string['pluginname'] = 'Assessment Path';
$string['page-mod-assessmentpath-x'] = 'Toutes les pages du module Assessment Path';

// Permissions

$string['assessmentpath:addinstance'] = 'Ajouter un nouveau parcours Assessment Path';
$string['assessmentpath:notifycompletion'] = 'Recevoir les notifications de complétion';


// Edit page

// Tabs layout
$string['maintab'] = 'Paramètres principaux';
$string['stepstab'] = 'Edition des étapes';
$string['step'] = 'Etape';
$string['steps'] = 'Etapes';
$string['test'] = 'Test';
$string['initial'] = 'Initial';
$string['remediation'] = 'Remédiation';
$string['initialtest'] = 'Test initial';
$string['remediationtest'] = 'Test de remédiation';
// Step commands
$string['deletestep'] = 'Supprimer';
$string['moveupstep'] = 'Déplacer vers le haut';
$string['movedownstep'] = 'Déplacer vers le bas';
$string['advancedstep'] = 'Paramètres avancés';
$string['insertstep'] = 'Insérer une nouvelle étape';

// Reports

// Report titles
$string['P0'] = 'Progression';
$string['P1'] = 'Résultats individuels';
$string['MyP1'] = 'Mes résultats';
$string['P2'] = 'Résultats collectifs globaux';
$string['P3'] = 'Résultats collectifs par parcours  "assessment path"';
$string['P4'] = 'Résultats collectifs par test';
// Export buttons
$string['exportbookP1'] = 'Export des utilisateurs (Excel)';
$string['exportbookP3'] = 'Export des parcours (Excel)';
$string['exportbookP4'] = 'Export détaillé (Excel)';
// Useful terms
$string['beforeremediation'] = 'Avant remédiation';
$string['afterremediation'] = 'Après remédiation';
$string['remediationaverage'] = 'Moyenne des apprenants en remédiation';
$string['path'] = 'Parcours';
$string['progress'] = 'Progression des parcours "assessment path"';
// Score adjustments
$string['scorefield'] = 'Editer';
$string['scorefield_R'] = 'Editer';
$string['scoreedit'] = 'Modifier les scores';
$string['scoresubmit'] = 'Enregistrer les nouveaux scores';
// Statistics
$string['back'] = 'Retour';
$string['statistics'] = 'Statistiques Quetzal';


// Comments

$string['savecomments'] = 'Enregistrer les commentaires';
$string['comments'] = 'Commentaires';
$string['testcomments'] = 'Commentaires du test';
$string['pathcomments'] = 'Commentaires du parcours';
$string['coursecomments'] = 'Commentaires du cours';


// File info browser

$string['step_filearea'] = '{$a->code}';
$string['test_initial_filearea'] = 'Initial';
$string['test_remediation_filearea'] = 'Remédiation';


// Notifications

$string['emailnotifybody'] = 'Bonjour {$a->username},

{$a->studentname} a terminé \'{$a->activityname}\' ({$a->activityurl}) dans le cours \'{$a->coursename}\'.

Vous pouvez suivre cette activité ici : {$a->activityreporturl}.';
$string['emailnotifysmall'] = '{$a->studentname} has completed {$a->activityname}. See {$a->activityreporturl}';
$string['emailnotifysubject'] = '{$a->studentname} has completed {$a->activityname}';

$string['messageprovider:completion'] = 'Complétion des activités Assessment Path';

