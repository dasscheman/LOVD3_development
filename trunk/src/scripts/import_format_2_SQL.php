#!/usr/bin/php
<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2013-02-18
 * Modified    : 2013-02-18
 * For LOVD    : 3.0-03
 *
 * Copyright   : 2004-2013 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Ing. Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
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

// This script reads out the gene and transcript LOVD import format and creates
// an SQL file that loads the data into the database preventing duplicated key
// errors. Once the import script learns how to process genes and transcripts,
// we will no longer need this script.

header('Content-type: text/plain; charset=UTF-8');
require '../inc-lib-init.php';
set_time_limit(0);

$sFileNameIn = 'LOVD_import_genes_and_transcripts.old.txt';





$aFileIn = lovd_php_file('./' . $sFileNameIn);
if ($aFileIn === false) {
    die('Input file could not be opened.' . "\n");
}
////////////////////////////////////////////////////////////////////////////////

// This code based on import code, since we're reading out an import file anyways.
$aParsed = array_fill_keys(
    array('Genes', 'Transcripts'),
    array('columns' => array(), 'nColumns' => 0));
// Prepare, find LOVD version and format type.
$sFileVersion = $sFileType = $sCurrentSection = '';
$bParseColumns = false;

foreach ($aFileIn as $i => $sLine) {
    $sLine = trim($sLine);
    if (!$sLine) {
        continue;
    }

    if (!$sFileVersion) {
        // Still looking for the LOVD version! We have a line here, so this must be what we're looking for.
        if (!preg_match('/^###\s*LOVD-version\s*([0-9]{4}\-[0-9]{2}[a-z0-9])\s*###\s*([^#]+)\s*###/', ltrim($sLine, '"'), $aRegs)) {
            die('Cannot recognize format of Gene and Transcript output file.' . "\n");
        } else {
            list(, $sFileVersion, $sFileType) = $aRegs;
            $sFileType = trim($sFileType);
        }
        break;
    }
}

// Now, the actual parsing...
foreach ($aFileIn as $i => $sLine) {
    $sLine = trim($sLine);
    if (!$sLine) {
        continue;
    }
    $nLine = $i + 1;

    if (substr(ltrim($sLine, '"'), 0, 1) == '#') {
        if (preg_match('/^##\s*([A-Za-z_]+)\s*##\s*Do not remove/', ltrim($sLine, '"'), $aRegs)) {
            // New section.
            // Clean up old section, if available.
            if ($sCurrentSection) {
                unset($aSection['columns']);
                unset($aSection['nColumns']);
            }
            $sCurrentSection = $aRegs[1];
            $bParseColumns = true;

            // So we can use short variables.
            $aSection = &$aParsed[$sCurrentSection];
            $aColumns = &$aSection['columns'];
            $nColumns = &$aSection['nColumns'];
        } // Else, it's just comments we will ignore.
        continue;
    }

    if ($bParseColumns) {
        // We are expecting columns now, because we just started a new section.
        if (!preg_match('/^(("\{\{[A-Za-z_\/]+\}\}"|\{\{[A-Za-z_\/]+\}\})\t)+$/', $sLine . "\t")) { // FIXME: Can we make this a simpler regexp?
            // Columns not found; either we have data without a column header, or a malformed column header. Abort import.
            die('Error (' . $sCurrentSection . ', line ' . $nLine . '): Expected column header, got this instead:<BR><BLOCKQUOTE>' . htmlspecialchars($sLine) . '</BLOCKQUOTE>');
            break;
        }

        $aColumns = explode("\t", $sLine);
        $nColumns = count($aColumns);
        $aColumns = array_map('trim', $aColumns, array_fill(0, $nColumns, '"{ }'));

        $bParseColumns = false;
        continue; // Continue to the next line.
    }

    if (!$sCurrentSection) {
        // We got here, without passing a section header first.
        die('Error (line ' . $nLine . '): Found data before finding section header.');
    }

    // We've got a line of data here. Isolate the values and check all columns.
    $aLine = explode("\t", $sLine);
    $aLine = array_map('trim', $aLine, array_fill(0, $nColumns, '" '));

    // Tag all fields with their respective column name. Then check data.
    $aLine = array_combine($aColumns, array_values($aLine));

    // Generate SQL.
    if ($sCurrentSection == 'Transcripts') {
        $aLine['id'] = NULL;
    }
    print('INSERT IGNORE INTO lovd_' . strtolower($sCurrentSection) . ' (' . implode(', ', $aColumns) . ') VALUES ("' . implode('", "', $aLine) . '");' . "\n");
}

// Clean up old section, if available.
if ($sCurrentSection) {
    unset($aSection['columns']);
    unset($aSection['nColumns']);
}
?>
