<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2014-12-09
 * Modified    : 2015-02-26
 * For LOVD    : 3.0-13
 *
 * Copyright   : 2004-2015 Leiden University Medical Center; http://www.LUMC.nl/
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

define('ROOT_PATH', '../');
require ROOT_PATH . 'inc-init.php';
require ROOT_PATH . 'inc-lib-form.php';

// But we don't care about your session (in fact, it locks the whole LOVD if we keep this page running).
session_write_close();
@set_time_limit(0);

define('PAGE_TITLE', 'Get variant info');
$_T->printHeader(false); // We'll use the "clean" template.
$_T->printTitle();





function lovd_getExonNumberForPosition ($aExons, $nMain, $nOffset = 0)
{
    // Returns the position's exon number, based on the $aExon exon table (in Mutalyzer format, non-star notation).

    // FIXME: No checks built in yet... $aExon format, $nMain and $nOffset formats...

    $sExon = '';
    $nLastExon = count($aExons);
    foreach ($aExons as $nExon => $aExon) {
        $nExon ++;

        // NOTE: We're not supporting the suggested notation to use '_1' for 'upstream of exon 1'.
        if (($nExon == 1 && $nMain < $aExon['cStart']) || ($nMain >= $aExon['cStart'] && $nMain <= $aExon['cStop'])) {
            // Upstream of 1st exon, or within any exon boundaries.
            // Now check if we're in the intron *before* this exon, actually.
            if ($nExon != 1 && $nOffset && $nOffset < 0) {
                // Oops. We're actually in the intron *before* the exon!
                $sExon = ($nExon - 1) . 'i';
            } else {
                $sExon = $nExon;
                // Now check if we're in the intron *after* this exon!
                if ($nExon != $nLastExon && $nOffset && $nOffset > 0) {
                    // Oops. We're actually in the intron *before* the exon!
                    $sExon .= 'i';
                }
            }
        }
    }

    // If we have no exon notation yet, it might have been past the last exon?
    // NOTE: We're not supporting the suggested notation to use '10_' for 'downstream of exon 10'.
    if (!$sExon) {
        $nExonEnd = $aExons[$nLastExon - 1]['cStop'];
        if ($nMain > $nExonEnd) {
            $sExon = $nLastExon;
        }
    }

    return $sExon;
}





if (POST) {
    lovd_errorClean();

    // Check field, check data structure, check genes and transcripts, etc.
    if (empty($_POST['variants']) || !trim($_POST['variants'])) {
        lovd_errorAdd('variants', 'No variant data found.');
    } else {
        $aUDs = array(); // 'GENE' => 'UD'
        $aTranscripts = array(); // 'NM_000000.1' => array('00001', '001'); (transcript ID, mutalyzer ID)
        $aTranscriptInfo = array(); // 'NM_000000.1' => array(transcript_info)
        $aData = explode("\r\n", $_POST['variants']); // No trim here, because we want to respect empty lines.

        // Loop variants, check genes and transcripts.
        foreach ($aData as $sLine) {
            if (!trim($sLine)) {
                continue;
            }

            // Note, list is being parsed twice. If this code is changed, change also the code further below.
            $aLine = preg_split('/\s+/', $sLine, 3);
            if (count($aLine) != 3) {
                continue;
            }
            list($sGene, $sTranscript, $sVariant) = $aLine;

            // Check gene.
            // I'm guessing I'm not getting many different genes, so I'm not pulling out the
            // entire list from the database, but I query the database whenever necessary.
            if (!isset($aUDs[$sGene])) {
                $sUD = $_DB->query('SELECT refseq_UD FROM ' . TABLE_GENES . ' WHERE id = ?', array($sGene))->fetchColumn();
                $aUDs[$sGene] = $sUD; // Even if we don't have an UD, just set the key because I don't want to keep checking.
                if (!$sUD) {
                    lovd_errorAdd('variants', 'Gene not found in database, or no UD: ' . htmlspecialchars($sGene) . '.');
                }
            }

            // Check transcript.
            // I'm guessing I'm not getting many different transcripts, so I'm not pulling out the
            // entire list from the database, but I query the database whenever necessary.
            if (!isset($aTranscripts[$sTranscript])) {
                // Even if we don't have a Mutalyzer ID, just set the key because I don't want to keep checking.
                $aTranscripts[$sTranscript] = $_DB->query('SELECT id, id_mutalyzer FROM ' . TABLE_TRANSCRIPTS . ' WHERE id_ncbi = ?', array($sTranscript))->fetchRow();
                if (!is_array($aTranscripts[$sTranscript]) || empty($aTranscripts[$sTranscript][1])) {
                    lovd_errorAdd('variants', 'Transcript not found in database, or no Mutalyzer ID: (' . htmlspecialchars($sGene) . ') ' . htmlspecialchars($sTranscript) . '.');
                }
            }
        }
    }



    if (!lovd_error()) {
        // Now, only build the results, when there are no errors.
        // Load progress bar.
        require ROOT_PATH . 'class/progress_bar.php';
        $_BAR = new ProgressBar('variants', 'Collecting information...');
        $aVariants = array();

        // Loop variants, get all resources...
        $nLines = count($aData);
        foreach ($aData as $nLine => $sLine) {
            if (!trim($sLine)) {
                // Empty line. We'll respect it in the output.
                $aVariants[] = $sLine;
                continue;
            }

            $aLine = preg_split('/\s+/', $sLine, 3);
            if (count($aLine) != 3) {
                $aVariants[] = $sLine;
                continue;
            }
            list($sGene, $sTranscript, $sVariant) = $aLine;

            $aVariant = array($sTranscript . '(' . $sGene . '):' . $sVariant, ''); // Put the input already, and make space already for the chromosome.
            // Position fields, first.
            // FIXME: NGS branch contains a function that calculates these fields...
            $sJSONResponse = @implode('', file('https://mutalyzer.nl/json/mappingInfo?LOVD_ver=' . $_SETT['system']['version'] . '&build=' . $_CONF['refseq_build'] . '&accNo=' . rawurlencode($sTranscript) . '&variant=' . rawurlencode($sVariant)));
            if ($sJSONResponse && $aResponse = json_decode($sJSONResponse, true)) {
                // The mappingInfo module call does not sort the positions, and as such the "start" and "end" can be in the "wrong" order.
                $aVariant[] = min($aResponse['start_g'], $aResponse['end_g']);
                $aVariant[] = max($aResponse['start_g'], $aResponse['end_g']);
                $aVariant[] = $aResponse['mutationType'];
                $aVariant[] = ''; // Reserved for genomic DNA, key 5.
                $aVariant[] = ''; // Reserved for transcript ID, key 6.
                $aVariant[] = $aResponse['startmain'];
                $aVariant[] = $aResponse['startoffset'];
                $aVariant[] = $aResponse['endmain'];
                $aVariant[] = $aResponse['endoffset'];
            } else {
                // Silent error.
                $aVariant[] = 'Can not parse mappingInfo output for ' . htmlspecialchars($sTranscript) . ':' . htmlspecialchars($sVariant) . '.';
                $aVariants[] = $aVariant;
                continue;
//                $_BAR->setMessage('Can not parse mappingInfo output for ' . htmlspecialchars($sTranscript) . ':' . htmlspecialchars($sVariant) . '.');
//                $_T->printFooter();
//                exit;
            }

            // Add VOG DNA field, using number conversion.
            $sJSONResponse = @implode('', file('https://mutalyzer.nl/json/numberConversion?build=' . $_CONF['refseq_build'] . '&gene=' . rawurlencode($sGene) . '&variant=' . rawurlencode($sTranscript . ':' . $sVariant)));
            if ($sJSONResponse && $aResponse = json_decode($sJSONResponse, true)) {
                foreach ($aResponse as $sMappedVariant) {
                    // First one should be the one, actually.
                    if (substr($sMappedVariant, 0, 3) == 'NC_') {
                        $aMappedVariant = explode(':', $sMappedVariant, 2);
                        $sChr = array_search($aMappedVariant[0], $_SETT['human_builds'][$_CONF['refseq_build']]['ncbi_sequences']);
                        if ($sChr) {
                            $aVariant[1] = $sChr;
                            $aVariant[5] = $aMappedVariant[1]; // Should be reserved when putting the position fields.
                            break;
                        }
                    }
                }
            }
            if (count($aVariant) < 11) {
                // No NC variant found.
                // Silent error.
                $aVariant[] = 'Can not parse numberConversion output for ' . htmlspecialchars($sTranscript) . ':' . htmlspecialchars($sVariant) . '.';
                $aVariants[] = $aVariant;
                continue;
//                $_BAR->setMessage('Can not parse numberConversion output for ' . htmlspecialchars($sTranscript) . ':' . htmlspecialchars($sVariant) . '.');
//                $_T->printFooter();
//                exit;
            }

            // For exon numbering, we need the transcript information, to see where the CDS ends,
            // because Exon positions are not in the same format as the mappingInfo positions are.
            if (!isset($aTranscriptInfo[$sTranscript])) {
                $sJSONResponse = @implode('', file('https://mutalyzer.nl/json/transcriptInfo?LOVD_ver=' . $_SETT['system']['version'] . '&build=' . $_CONF['refseq_build'] . '&accNo=' . rawurlencode($sTranscript)));
                if ($sJSONResponse && $aResponse = json_decode($sJSONResponse, true)) {
                    $aTranscriptInfo[$sTranscript] = $aResponse;
                } else {
                    // Silent error.
                    $aVariant[] = 'Can not parse transcriptInfo output for ' . htmlspecialchars($sTranscript) . '.';
                    $aVariants[] = $aVariant;
                    continue;
//                    $_BAR->setMessage('Can not parse transcriptInfo output for ' . htmlspecialchars($sTranscript) . '.');
//                    $_T->printFooter();
//                    exit;
                }
            }

            // Now add Exon, cDNA, RNA, and protein fields. We need the nameChecker.
            $sUD = $aUDs[$sGene];
            list($nTranscriptID, $sMutalyzerID) = $aTranscripts[$sTranscript];
            $aVariant[6] = $nTranscriptID; // Should be reserved when putting the position fields.
            $sExon = $sRNA = $sProtein = '';
            $sJSONResponse = @implode('', file('https://mutalyzer.nl/json/runMutalyzer?variant=' . rawurlencode($sUD . '(' . $sGene . '_v' . $sMutalyzerID . '):' . $sVariant)));
            if ($sJSONResponse && $aResponse = json_decode($sJSONResponse, true)) {
                if (!isset($aResponse['proteinDescriptions'])) {
                    // Not sure if this can happen using JSON.
                    $aResponse['proteinDescriptions'] = array();
                }

                // Predict RNA && Protein change.
                // 'Intelligent' error handling.
//var_dump($sVariant, $aResponse['messages']);
                foreach ($aResponse['messages'] as $aError) {
                    // FIXME; We should include ERANGE error handling here too, when we can expect large deletions etc.
                    // Pass other errors on to the users?
                    if (isset($aError['errorcode']) && $aError['errorcode'] == 'WSPLICE') {
                        $sRNA = 'r.spl?';
                        $sProtein = 'p.?';
                        break;
                    }
                }
                if (!$sProtein && !empty($aResponse['proteinDescriptions'])) {
                    foreach ($aResponse['proteinDescriptions'] as $sVariantOnProtein) {
                        if (($nPos = strpos($sVariantOnProtein, $sGene . '_i' . $sMutalyzerID . '):p.')) !== false) {
                            // FIXME: Since this code is the same as the code used in the variant mapper (2x), better make a function out of it.
                            $sProtein = substr($sVariantOnProtein, $nPos + strlen($sGene . '_i' . $sMutalyzerID . '):'));
                            if ($sProtein == 'p.?') {
                                $sRNA = 'r.?';
                            } elseif ($sProtein == 'p.(=)') {
                                // FIXME: Not correct in case of substitutions e.g. in the third position of the codon, not leading to a protein change.
                                $sRNA = 'r.(=)';
                            } else {
                                // RNA will default to r.(?).
                                $sRNA = 'r.(?)';
                            }
                            break;
                        }
                    }
                }

                // Calculate Exon number.
                list($nVariantStart, $nVariantStartOffset, $nVariantEnd, $nVariantEndOffset) = array($aVariant[7], $aVariant[8], $aVariant[9], $aVariant[10]);
                // Fix star-notation to non-star notation.
                foreach ($aResponse['exons'] as $nExon => $aExon) {
                    // First, fix "star-notation" to "non-star notation".
                    if (substr($aExon['cStart'], 0, 1) == '*') {
                        $aResponse['exons'][$nExon]['cStart'] = (int) substr($aExon['cStart'], 1) + $aTranscriptInfo[$sTranscript]['CDS_stop'];
                    }
                    if (substr($aExon['cStop'], 0, 1) == '*') {
                        $aResponse['exons'][$nExon]['cStop'] = (int) substr($aExon['cStop'], 1) + $aTranscriptInfo[$sTranscript]['CDS_stop'];
                    }
                }

                // Note: We're assuming here, that the $nVariantEnd is not lower than $nVariantStart.
                $sExon = lovd_getExonNumberForPosition($aResponse['exons'], $nVariantStart, $nVariantStartOffset);
                $sExonEnd = lovd_getExonNumberForPosition($aResponse['exons'], $nVariantEnd, $nVariantEndOffset);
                if ($sExon != $sExonEnd) {
                    $sExon .= '_' . $sExonEnd;
                }
            }
            // Any errors related to the prediction of Exon, RNA or Protein are silently ignored.



            // Store information.
            $aVariant[] = $sExon;
            $aVariant[] = $sVariant; // FIXME: If we'd want to be precise, we should take this out of the name checker...
            $aVariant[] = $sRNA;
            $aVariant[] = $sProtein;

            // Now store the entire variant in the data array.
            $aVariants[] = $aVariant;

            $_BAR->setProgress(($nLine/$nLines)*100);
        }
        $_BAR->setProgress(100);
        $_BAR->setMessage('Done!');



        // Now print everything to the screen.
        print('<PRE class="S11" style="border: 1px solid #000;">' . "\n" .
              "Input\t{{chromosome}}\t{{position_g_start}}\t{{position_g_end}}\t{{type}}\t{{VariantOnGenome/DNA}}\t{{transcriptid}}\t{{position_c_start}}\t{{position_c_start_intron}}\t{{position_c_end}}\t{{position_c_end_intron}}\t{{VariantOnTranscript/Exon}}\t{{VariantOnTranscript/DNA}}\t{{VariantOnTranscript/RNA}}\t{{VariantOnTranscript/Protein}}\n");
        foreach ($aVariants as $aVariant) {
            if (!is_array($aVariant)) {
                // Empty lines, etc.
                print("\n");
            } else {
                print(implode("\t", $aVariant) . "\n");
            }
        }
        print('</PRE>');

        $_T->printFooter();
        exit;
    }
}



lovd_showInfoTable('Please paste your variant info in the box below, one variant per line, three columns with tabs or spaces in between them: Gene, Transcript, Variant.<BR>Example:<BR><PRE>FOXG1	NM_005249.4	c.*100del</PRE><B>Please note that all genes and transcripts are required to be already present in this LOVD installation.</B><BR>Genomic positions are relative to build: ' . $_CONF['refseq_build'] . '.', 'information');
lovd_errorPrint();

// Table.
print('      <FORM action="' . CURRENT_PATH . '?' . ACTION . '" method="post">' . "\n");

// Array which will make up the form table.
$aForm = array(
    array('POST', '', '', '', '20%', '14', '80%'),
    array('Paste variant data', '', 'textarea', 'variants', 50, 40),
    'skip',
    array('', '', 'submit', 'Get variant info'),
);
lovd_viewForm($aForm);

print('</FORM>' . "\n\n");

$_T->printFooter();
exit;
?>
