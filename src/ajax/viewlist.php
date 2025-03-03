<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2010-02-18
 * Modified    : 2015-05-12
 * For LOVD    : 3.0-14
 *
 * Copyright   : 2004-2015 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmers : Ing. Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
 *               Ing. Ivar C. Lugtenburg <I.C.Lugtenburg@LUMC.nl>
 *               Msc. Daan Asscheman <D.Asscheman@LUMC.nl>
 *
 *
 * This file is part of LOVD.
 *
 * LOVD is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * LOVD is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with LOVD.  If not, see <http://www.gnu.org/licenses/>.
 *
 *************/

define('ROOT_PATH', '../');
require ROOT_PATH . 'inc-init.php';

if (empty($_GET['viewlistid']) || empty($_GET['object']) || !preg_match('/^[A-Z_]+$/i', $_GET['object'])) {
    die(AJAX_DATA_ERROR);
}

// The required security to load the viewList() depends on the data that is shown.
// To prevent security problems if we forget to set a requirement here, we default to LEVEL_ADMIN.
$aNeededLevel =
         array(
                'Column' => LEVEL_CURATOR,
                'Custom_ViewList' => 0,
                'Disease' => 0,
                'Gene' => 0,
                'Genome_Variant' => 0,
                'Individual' => 0,
                'Link' => LEVEL_MANAGER,
                'Log' => LEVEL_MANAGER,
                'Phenotype' => 0,
                'Screening' => 0,
                'Shared_Column' => LEVEL_CURATOR,
                'Transcript' => 0,
                'Transcript_Variant' => 0,
                'User' => LEVEL_MANAGER,
              );
if (isset($aNeededLevel[$_GET['object']])) {
    $nNeededLevel = $aNeededLevel[$_GET['object']];
} else {
    $nNeededLevel = LEVEL_ADMIN;
}

// 2013-06-28; 3.0-06; We can't allow just any custom viewlist without actually checking the shown objects. Screenings, for instance, does not have a built-in status check (since it doesn't have a status).
// Building list of allowed combinations of objects for custom viewlists.
if ($_GET['object'] == 'Custom_ViewList' && (!isset($_GET['object_id']) || !in_array($_GET['object_id'],
            array(
                'VariantOnGenome,Scr2Var,VariantOnTranscript', // Variants on I and S VEs.
                'Transcript,VariantOnTranscript,VariantOnGenome', // IN_GENE.
                'VariantOnTranscript,VariantOnGenome', // Gene-specific variant view.
                'VariantOnTranscriptUnique,VariantOnGenome', // Gene-specific unique variant view.
                'VariantOnTranscript,VariantOnGenome,Screening,Individual', // Gene-specific full data view.
                'Gene,Transcript,DistanceToVar')))) { // Map variant to transcript.
    die(AJAX_DATA_ERROR);
}

// We can't authorize Curators and Collaborators without loading their level!
// 2014-03-13; 3.0-10; Collaborators should of course also get their level loaded!
if ($_AUTH['level'] < LEVEL_MANAGER && (!empty($_AUTH['curates']) || !empty($_AUTH['collaborates']))) {
    if ($_GET['object'] == 'Column') {
        lovd_isAuthorized('gene', $_AUTH['curates']); // Any gene will do.
    } elseif ($_GET['object'] == 'Transcript' && isset($_GET['search_geneid']) && preg_match('/^="([^"]+)"$/', $_GET['search_geneid'], $aRegs)) {
        lovd_isAuthorized('gene', $aRegs[1]); // Authorize for the gene currently searched (it currently restricts the view).
    } elseif ($_GET['object'] == 'Shared_Column' && isset($_GET['object_id'])) {
        lovd_isAuthorized('gene', $_GET['object_id']); // Authorize for the gene currently loaded.
    } elseif ($_GET['object'] == 'Custom_ViewList' && isset($_GET['id'])) {
        // 2013-06-28; 3.0-06; We can't just authorize users based on the given ID without actually checking the shown objects and checking if the search results are actually limited or not.
        // CustomVL_VOT_for_I_VE has no ID and does not require authorization (only public VOGs loaded).
        // CustomVL_VOT_for_S_VE has no ID and does not require authorization (only public VOGs loaded).
        // CustomVL_IN_GENE has no ID and does not require authorization (only public VOGs loaded).

        // CustomVL_VOT_VOG_<<GENE>> is restricted per gene in the object argument, and search_transcriptid should contain a transcript ID that matches.
        // CustomVL_VIEW_<<GENE>> is restricted per gene in the object argument, and search_transcriptid should contain a transcript ID that matches.
        if (in_array($_GET['object_id'], array('VariantOnTranscript,VariantOnGenome', 'VariantOnTranscriptUnique,VariantOnGenome', 'VariantOnTranscript,VariantOnGenome,Screening,Individual')) && (!isset($_GET['search_transcriptid']) || !$_DB->query('SELECT COUNT(*) FROM ' . TABLE_TRANSCRIPTS . ' WHERE id = ? AND geneid = ?', array($_GET['search_transcriptid'], $_GET['id']))->fetchColumn())) {
            die(AJAX_NO_AUTH);
        }
        lovd_isAuthorized('gene', $_GET['id']); // Authorize for the gene currently loaded.
    }
}

// Require special clearance?
if ($nNeededLevel && (!$_AUTH || $_AUTH['level'] < $nNeededLevel)) {
    // If not authorized, die with error message.
    die(AJAX_NO_AUTH);
}

// Managers, and sometimes curators, are allowed to download lists...
if (in_array(ACTION, array('download', 'downloadSelected'))) {
    if ($_AUTH['level'] >= LEVEL_CURATOR) {
        // We need this define() because the Object::viewList() may still throw some error which calls
        // Template::printHeader(), which would then thow a "text/plain not allowed here" error.
        define('FORMAT_ALLOW_TEXTPLAIN', true);
    }
}
if (FORMAT == 'text/plain' && !defined('FORMAT_ALLOW_TEXTPLAIN')) {
    die(AJAX_NO_AUTH);
}

$sFile = ROOT_PATH . 'class/object_' . strtolower($_GET['object']) . 's.php';

if (!file_exists($sFile)) {
    header('HTTP/1.0 404 Not Found');
    exit;
}



$sObjectID = '';
$nID = '';
if (in_array($_GET['object'], array('Custom_ViewList', 'Phenotype', 'Shared_Column', 'Transcript_Variant'))) {
    if (isset($_GET['object_id'])) {
        $sObjectID = $_GET['object_id'];
    }
    if (isset($_GET['id'])) {
        $nID = $_GET['id'];
    }
}
require $sFile;
$_GET['object'] = 'LOVD_' . str_replace('_', '', $_GET['object']); // FIXME; test dit op een windows, test case-insensitivity.
$aColsToSkip = (!empty($_GET['skip'])? $_GET['skip'] : array());
$_DATA = new $_GET['object']($sObjectID, $nID);
// Set $bHideNav to false always, since this ajax request could only have been sent if there were navigation buttons.
$_DATA->viewList($_GET['viewlistid'], $aColsToSkip, (!empty($_GET['nohistory'])? true : false), (!empty($_GET['hidenav'])? true : false), (!empty($_GET['options'])? true : false), (!empty($_GET['only_rows'])? true : false));
?>
