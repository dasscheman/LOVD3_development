#!/usr/bin/php
<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2013-02-13
 * Modified    : 2015-10-08
 * For LOVD    : 3.0-14
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

// This script reads out the HGNC file and creates an LOVD3 import file format
// with the gene information. It checks on LOVD.nl whether or not to use LRG, NG
// or NC. It also queries Mutalyzer for the reference transcript's information,
// and puts these in the file, too.


// TODO: if the transcript in the current file has not been changed, there is no
// need to query Mutalyzer again.
// TODO: The LOVD3 import script needs to be extended to be able to import this
// information. For now, I will write something that creates SQL out of these
// entries.

header('Content-type: text/plain; charset=UTF-8');
require '../inc-lib-init.php';
set_time_limit(0);
ini_set('memory_limit', -1); // Better not suck up all I have here...!

$aHGNCNeededColumns =
    array(
        'gd_hgnc_id' => 'HGNC ID',
        'gd_app_sym' => 'Approved Symbol',
        'gd_app_name' => 'Approved Name',
        'gd_locus_type' => 'Locus Type',
        'gd_locus_group' => 'Locus Group',
        'gd_pub_chrom_map' => 'Chromosome',
        'gd_pub_eg_id' => 'Entrez Gene ID', // Curated by the HGNC; there is a second one with the exact same column header, but we're ignoring that one.
        'gd_pub_refseq_ids' => 'RefSeq IDs', // Curated by the HGNC.
        'md_mim_id' => 'OMIM ID(supplied by OMIM)',
        'md_refseq_id' => 'RefSeq(supplied by NCBI)', // Downloaded from external sources.
    );

$aLOVDGeneColumns =
    array(
        'id',
        'name',
        'chromosome',
        'chrom_band',
        'refseq_genomic',
        'refseq_UD',
        'id_hgnc',
        'id_entrez',
        'id_omim',
    );

$aLOVDTranscriptColumns =
    array(
        'id',
        'geneid',
        'name',
        'id_mutalyzer',
        'id_ncbi',
        'id_protein_ncbi',
        'position_c_mrna_start',
        'position_c_mrna_end',
        'position_c_cds_end',
        'position_g_mrna_start',
        'position_g_mrna_end',
    );

$sFileNameOut = 'LOVD_import_genes_and_transcripts.txt';

$_SETT = array(
    'system' =>
    array(
        'version' => '3.0-02',
    ),
    'human_builds' =>
    array(
        'hg19' =>
        array(
            'ncbi_name'      => 'GRCh37',
            'ncbi_sequences' =>
            array(
                '1'  => 'NC_000001.10',
                '2'  => 'NC_000002.11',
                '3'  => 'NC_000003.11',
                '4'  => 'NC_000004.11',
                '5'  => 'NC_000005.9',
                '6'  => 'NC_000006.11',
                '7'  => 'NC_000007.13',
                '8'  => 'NC_000008.10',
                '9'  => 'NC_000009.11',
                '10' => 'NC_000010.10',
                '11' => 'NC_000011.9',
                '12' => 'NC_000012.11',
                '13' => 'NC_000013.10',
                '14' => 'NC_000014.8',
                '15' => 'NC_000015.9',
                '16' => 'NC_000016.9',
                '17' => 'NC_000017.10',
                '18' => 'NC_000018.9',
                '19' => 'NC_000019.9',
                '20' => 'NC_000020.10',
                '21' => 'NC_000021.8',
                '22' => 'NC_000022.10',
                'X'  => 'NC_000023.10',
                'Y'  => 'NC_000024.9',
                'M'  => 'NC_012920.1',
            ),
        ),
    ),
);

// We ignore genes from the following locus groups:
$aBadLocusGroups =
    array(
        'phenotype', // No transcripts.
        'withdrawn', // Do not exist anymore.
    );

// We ignore genes from the following locus types (most of these are in group "other"):
$aBadLocusTypes =
    array(
        'RNA, cluster',  // From group "non-coding RNA", none of them work (verified).
        'RNA, transfer', // From group "non-coding RNA", none of them work (verified, these are actually all mitochondria).
        'endogenous retrovirus',  // From group "other", none of them work (verified).
        'fragile site',           // From group "other", none of them work (verified).
        'immunoglobulin gene',    // From group "other", none of them work (verified).
        'region',                 // From group "other", none of them work (verified).
        'transposable element',   // From group "other", none of them work (verified).
        'unknown',                // From group "other", none of them work (verified).
        'virus integration site', // From group "other", none of them work (verified).
        'immunoglobulin pseudogene', // From group "pseudogene", none of them work (verified).
    );





function lovd_getUDForGene ($sBuild, $sGene)
{
    // Retrieves an UD for any given gene and genome build.
    // In principle, any build is supported, but we'll check against the available builds supported in LOVD.
    global $_CONF, $_SETT;

    if (!$sBuild || !is_string($sBuild) || !isset($_SETT['human_builds'][$sBuild])) {
        return false;
    }

    if (!$sGene || !is_string($sGene)) {
        return false;
    }

    $sUD = '';

    // Let's get the mapping information.
    $sJSONResponse = @file_get_contents(str_replace('/services', '', $_CONF['mutalyzer_soap_url']) . '/json/getGeneLocation?build=' . $sBuild . '&gene=' . $sGene);
    if ($sJSONResponse && $aResponse = json_decode($sJSONResponse, true)) {
        $sChromosome = $_SETT['human_builds'][$sBuild]['ncbi_sequences'][substr($aResponse['chromosome_name'], 3)];
        $nStart = $aResponse['start'] - ($aResponse['orientation'] == 'forward'? 5000 : 2000);
        $nEnd = $aResponse['stop'] + ($aResponse['orientation'] == 'forward'? 2000 : 5000);
        $sJSONResponse = @file_get_contents(str_replace('/services', '', $_CONF['mutalyzer_soap_url']) . '/json/sliceChromosome?chromAccNo=' . $sChromosome . '&start=' . $nStart . '&end=' . $nEnd . '&orientation=' . ($aResponse['orientation'] == 'forward'? 1 : 2));
        if ($sJSONResponse && $aResponse = json_decode($sJSONResponse, true)) {
            $sResponse = (!is_array($aResponse)? $aResponse : implode('', $aResponse));
            $sUD = $sResponse;
        }
    }

    return $sUD;
}





print("Reading HGNC file...\n");

$aHGNCFile = lovd_php_file('./hgnc_filtered_set.txt');
if ($aHGNCFile === false) {
    die('HGNC file could not be opened.');
} else {
    print("Parsing file header...\n");
    flush();
    @ob_end_flush();
}

// We need very selective info from the HGNC:
$aColumnsInFile = explode("\t", $aHGNCFile[0]);
$aHGNCColumns = array();

// Determine the correct keys for the columns we want.
foreach ($aColumnsInFile as $nKey => $sName) {
    if ($sKey = array_search($sName, $aHGNCNeededColumns)) {
        // We need this column!
        $aHGNCColumns[$nKey] = $sKey;
    }
}

if (count($aHGNCColumns) < count($aHGNCNeededColumns)) {
    // We didn't find all needed columns!
    die("We could not find all columns, please check format.\n");
} else {
    print("Columns found, retrieving resource data...\n");
    unset($aHGNCFile[0]);
    flush();
}
$nHGNCGenes = count($aHGNCFile);

// Get list of LRGs and NGs to determine the genomic refseq of the genes.
$aLRGFile = lovd_php_file('http://www.lovd.nl/mirrors/lrg/LRG_list.txt');
unset($aLRGFile[0], $aLRGFile[1]);
$aLRGs = array();
foreach ($aLRGFile as $sLine) {
    $aLine = explode("\t", $sLine);
    $aLRGs[$aLine[1]] = $aLine[0];
}
$aNGFile = lovd_php_file('http://www.lovd.nl/mirrors/ncbi/NG_list.txt');
unset($aNGFile[0], $aNGFile[1]);
$aNGs = array();
foreach ($aNGFile as $sLine) {
    $aLine = explode("\t", $sLine);
    $aNGs[$aLine[0]] = $aLine[1];
}
if (!count($aLRGs) || !count($aNGs)) {
    die("Could not retrieve LRG and NG resources.\r\n");
} else {
    print("Resources stored, loading ignore list...\n");
    unset($aHGNCFile[0]);
    flush();
}

// Genes to igore are in genes_to_ignore.txt.
$aGenesToIgnore = array();
if (is_readable('./genes_to_ignore.txt')) {
    $aGenesToIgnore = lovd_php_file('./genes_to_ignore.txt');
    unset($aGenesToIgnore[0], $aGenesToIgnore[1]);
    print("List loaded, loading previous run...\n");
} else {
    print("No ignorelist present, loading previous run...\n");
}
flush();

// If we have run this script previously, we have some previous results in LOVD_import_genes_and_transcripts.old.txt.
$sFileNameOutPrev = str_replace('.txt', '.old.txt', $sFileNameOut);
$aParsed = array_fill_keys(
    array('Genes', 'Transcripts'),
    array('columns' => array(), 'data' => array(), 'nColumns' => 0));
if (is_readable('./' . $sFileNameOutPrev)) {
    $aPreviousRun = lovd_php_file('./' . $sFileNameOutPrev);

    // This code based on import code, since we're reading out an import file anyways.
    // Prepare, find LOVD version and format type.
    $sFileVersion = $sFileType = $sCurrentSection = '';
    $bParseColumns = false;

    foreach ($aPreviousRun as $i => $sLine) {
        $sLine = trim($sLine);
        if (!$sLine) {
            continue;
        }

        if (!$sFileVersion) {
            // Still looking for the LOVD version! We have a line here, so this must be what we're looking for.
            if (!preg_match('/^###\s*LOVD-version\s*([0-9]{4}\-[0-9]{2}[a-z0-9])\s*###\s*([^#]+)\s*###/', ltrim($sLine, '"'), $aRegs)) {
                die('Found an ' . $sFileNameOutPrev . ' file, but I cannot recognize its format.' . "\n");
            } else {
                list(, $sFileVersion, $sFileType) = $aRegs;
                $sFileType = trim($sFileType);
            }
            break;
        }
    }

    // Now, the actual parsing...
    foreach ($aPreviousRun as $i => $sLine) {
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

        // Per category, verify the data, including precise checks on specific columns.
        switch ($sCurrentSection) {
            case 'Genes':
                $aSection['data'][$aLine['id']] = $aLine;
                break;

            case 'Transcripts':
                $aSection['data'][$aLine['geneid']] = $aLine;
                break;
        }
    }

    // Clean up old section, if available.
    if ($sCurrentSection) {
        unset($aSection['columns']);
        unset($aSection['nColumns']);
    }
    print("Run loaded, parsing new data...\n");
} else {
    print("Could not load previous run, parsing new data...\n");
}
flush();



// Open file to write to.
$f = @fopen('./' . $sFileNameOut, 'w');
if (!$f) {
    die('Can not write to file!' . "\n");
}
fwrite($f, '### LOVD-version ' . lovd_calculateVersion($_SETT['system']['version']) . ' ### Full data download ### To import, do not remove or alter this header ###' . "\r\n" .
    '# charset = UTF-8' . "\r\n\r\n" .
    '## Genes ## Do not remove or alter this header ##' . "\r\n" .
    '{{' . implode("}}\t{{", $aLOVDGeneColumns) . "}}\r\n");
// Gene data can be written to the outfile immediately, transcripts we'll have to remember until we're done with the genes!
$aTranscriptsForLOVD = array();



// We're going to track some times, to see how much time we're spending using web resources.
$tStart = microtime(true);
$nGenes = 0;
$nTimeSpentGettingUDs = 0;
$nUDsRequested = 0;
$nTimeSpentGettingTranscripts = 0;
$nTranscriptsRequested = 0;



// Loop through the data and write it to the file.
print("\n");
foreach ($aHGNCFile as $nLine => $sLine) {
    // Write some statistics now and then, while we're waiting.
    if ($nGenes) { // This is put here, so that any continues used below don't stop the script from making output now and then).
        if (!($nGenes % 1)) {
            print('.');
            flush();
        }
        if (!($nGenes % 100)) {
            $nTimeSpent = microtime(true) - $tStart;
            $nTimeLeft = ($nHGNCGenes*$nTimeSpent/$nGenes)-$nTimeSpent;
            print("\n" .
                date('c') . '    Completed ' . $nGenes . ' genes (' . round(100*$nGenes/$nHGNCGenes) . '%) in ' . round($nTimeSpent, 1) . ' seconds (' . round($nTimeSpent/$nGenes, 2) . 's/gene); ETC is ' . round($nTimeLeft) . 's (' . date('c', ($nTimeLeft+time())) . ').' . "\n" .
                '    Requested ' . $nUDsRequested . ' UDs' . (!$nUDsRequested? '' : ', taking ' . round($nTimeSpentGettingUDs, 1) . ' seconds (' . round($nTimeSpentGettingUDs/$nUDsRequested, 2) . 's/UD)') . "\n" .
                '    Requested transcript info for ' . $nTranscriptsRequested . ' UDs' . (!$nTranscriptsRequested? '' : ', taking ' . round($nTimeSpentGettingTranscripts, 1) . ' seconds (' . round($nTimeSpentGettingTranscripts/$nTranscriptsRequested, 2) . 's/UD)') . "\n");
            flush();
        }
    }
    $nGenes ++;
    $aLineTMP = explode("\t", $sLine);
    $aLine = array();
    foreach ($aHGNCColumns as $nKey => $sName) {
        $aLine[$sName] = $aLineTMP[$nKey];
    }

    // Ignore genes from the bad locus groups.
    if (in_array($aLine['gd_locus_group'], $aBadLocusGroups)) {
        continue;
    }
    // Ignore genes from the bad locus types.
    if (in_array($aLine['gd_locus_type'], $aBadLocusTypes)) {
        continue;
    }
    // Ignore genes in our ignore list.
    if (in_array($aLine['gd_app_sym'], $aGenesToIgnore)) {
        continue;
    }

    // Prepare fields.... HGNC ID.
    $aLine['gd_hgnc_id'] = str_replace('HGNC:', '', $aLine['gd_hgnc_id']);
    // Chromosome fields.
    if ($aLine['gd_pub_chrom_map'] == 'mitochondria') {
        $sChromosome = 'M';
        $sChromBand = '';
    } elseif (preg_match('/^(\d{1,2}|[XY])(.*)$/', $aLine['gd_pub_chrom_map'], $aMatches)) {
        $sChromosome = $aMatches[1];
        $sChromBand = $aMatches[2];
    } else {
        continue;
    }
    // Genomic refseq...
    if (isset($aLRGs[$aLine['gd_app_sym']])) {
        $sRefseqGenomic = $aLRGs[$aLine['gd_app_sym']];
    } elseif (isset($aNGs[$aLine['gd_app_sym']])) {
        $sRefseqGenomic = $aNGs[$aLine['gd_app_sym']];
    } else {
        $sRefseqGenomic = $_SETT['human_builds']['hg19']['ncbi_sequences'][$sChromosome];
    }
    // UD... But we won't request it ofcourse, if we already have it!
    $sRefseqUD = (!isset($aParsed['Genes']['data'][$aLine['gd_app_sym']])? '' : $aParsed['Genes']['data'][$aLine['gd_app_sym']]['refseq_UD']);
    if (!$sRefseqUD) {
        $t = microtime(true);
        $sRefseqUD = lovd_getUDForGene('hg19', $aLine['gd_app_sym']);
        $nTimeSpentGettingUDs += (microtime(true) - $t);
        $nUDsRequested ++;
        if (!$sRefseqUD) {
            continue;
        }
    }



    // FIXME: Also here, you'll need to create something next time to make it faster after we've gathered transcripts before.
    // In principle, we're free to save the data now and look for transcripts later. But we want to be
    // sure we will be able to find transcripts. Otherwise, having the gene makes no sense...
    $sRefseqTranscript = '';
    $t = microtime(true);
    $sJSONResponse = @implode('', file('https://mutalyzer.nl/json/getTranscriptsAndInfo?genomicReference=' . $sRefseqUD . '&geneName=' . $aLine['gd_app_sym']));
    $nTimeSpentGettingTranscripts += (microtime(true) - $t);
    $nTranscriptsRequested ++;
    if ($sJSONResponse && $aResponse = json_decode($sJSONResponse, true)) {
        // We have to go three layers deep; through the response, then through the result, then read out TranscriptInfo.
        $aTranscriptsInUD = array();
        $aAvailableTranscripts = $aResponse;

        foreach($aAvailableTranscripts as $aAvailableTranscript) {
            if ($aAvailableTranscript['id']) { // Is this check needed? Copied from genes.php.
                list($sIDWithoutVersion, $nVersion) = explode('.', $aAvailableTranscript['id']);
                // We create a nested array like this, because possibly, we'll see two versions of one transcript.

                $aTranscriptsInUD[$sIDWithoutVersion][$nVersion] =
                    array(
                        'geneid' => $aLine['gd_app_sym'],
                        'name' => str_replace($aLine['gd_app_name'] . ', ', '', $aAvailableTranscript['product']),
                        'id_mutalyzer' => str_replace($aLine['gd_app_sym'] . '_v', '', $aAvailableTranscript['name']),
                        'id_ncbi' => $aAvailableTranscript['id'],
                        'id_protein_ncbi' => $aAvailableTranscript['proteinTranscript']['id'],
                        'position_c_mrna_start' => $aAvailableTranscript['cTransStart'],
                        'position_c_mrna_end' => $aAvailableTranscript['sortableTransEnd'],
                        'position_c_cds_end' => $aAvailableTranscript['cCDSStop'],
                        'position_g_mrna_start' => $aAvailableTranscript['chromTransStart'],
                        'position_g_mrna_end' => $aAvailableTranscript['chromTransEnd'],
                    );
            }
        }

        if (count($aTranscriptsInUD)) {
            // Now we must make a choice based on the transcripts we found. During mapping, we actually verify our choice by performing
            // multiple mappings and picking the first successful one, but we don't have that luxery right now (although we could
            // do fake mapping if we want to).
            // By limiting ourselves to transcripts found in the UD we automatically filter the transcripts; HGNC for instance,
            // suggests NC, NG, NM, NP, NR, XM, XR, NT and YP refseqs.
            // 1) We'll try the transcript(s) provided by the HGNC. If they provide more, we'll just pick the first one that can be found in the UD.
            $aTranscriptsFromHGNC = preg_split('/\s?[,;]\s?/', $aLine['gd_pub_refseq_ids']); // Currently HGVS is splitting on ' ,', but this is more flexible.
            // And the refseq provided by the HGNC that they got from somewhere else. Could also be NGs etc, but never more than one.
            $aTranscriptsFromHGNC[] = $aLine['md_refseq_id'];
            foreach ($aTranscriptsFromHGNC as $sTranscriptID) {
                // HGNC doesn't often use versions in the transcripts they have stored, but sometimes they do.
                // We're currently ignoring any version number given by HGNC.
                $sIDWithoutVersion = preg_replace('/\.\d+$/', '', $sTranscriptID);
                if (isset($aTranscriptsInUD[$sIDWithoutVersion])) {
                    // We might have different versions here in this array. Pick the highest one.
                    $nVersion = max(array_keys($aTranscriptsInUD[$sIDWithoutVersion]));
                    $sRefseqTranscript = $sIDWithoutVersion . '.' . $nVersion;
                    // Done, stop searching for transcripts.
                    break;
                }
            }
            // 2) Here we'll have some 9.500 genes left. Normally, we would now go find an LOVD and request that LOVD's API to see which transcript that one uses.
            // But that will slow this script down a lot (~1hr at 4 requests a sec), and we want to be fast...!
            // FIXME: Perhaps find a nice solution for this? Being able to get to the LOVD file itself already helps a lot!
            /*
            if (!$sRefseqTranscript) {
                // The HGNC does not have a transcript accession for this gene. Get one from LOVD.
                // FIXME; don't use file_get_contents() but instead lovd_php_file().
                $sGeneLink = @substr($sGeneLink = @file_get_contents('http://www.lovd.nl/' . $sSymbol . '?getURL'), 0, @strpos($sGeneLink, "\n"));
                $aGeneInfo = @explode("\n", @file_get_contents($sGeneLink . 'api/rest.php/genes/' . $sSymbol));
                if (!empty($aGeneInfo) && is_array($aGeneInfo)) {
                    foreach ($aGeneInfo as $sLine) {
                        preg_match('/refseq_mrna[\s]*:[\s]*([\S]+\.[\S]+)/', $sLine, $aMatches);
                        if (!empty($aMatches)) {
                            $aRefseqsTranscript[] = array('id_ncbi' => $aMatches[1]);
                            break;
                        }
                    }
                }
            }
            */
            // So OK, we'll just grab the first transcript found in the UD!
            if (!$sRefseqTranscript) {
                // We couldn't get any recommended transcripts from HGNC or the LOVD api, so we will just default to the first transcript that Mutalyzer returns.
                $sIDWithoutVersion = key($aTranscriptsInUD);
                // We might have different versions here in this array. Pick the highest one.
                $nVersion = max(array_keys($aTranscriptsInUD[$sIDWithoutVersion]));
                $sRefseqTranscript = $sIDWithoutVersion . '.' . $nVersion;
            }
        }
    }
    if (!$sRefseqTranscript) {
        continue;
    }
    list($sIDWithoutVersion, $nVersion) = explode('.', $sRefseqTranscript);
    $aTranscriptsForLOVD[$aLine['gd_app_sym']] = $aTranscriptsInUD[$sIDWithoutVersion][$nVersion];



    // We're done with this gene. We've got the gene data, we've got the transcript data.
    // Write to download file, and continue to the next gene.
    // First, write data to download file.
    fwrite($f, '"' . implode("\"\t\"",
        array(
            $aLine['gd_app_sym'],
            $aLine['gd_app_name'],
            $sChromosome,
            $sChromBand,
            $sRefseqGenomic,
            $sRefseqUD,
            $aLine['gd_hgnc_id'],
            $aLine['gd_pub_eg_id'],
            $aLine['md_mim_id'],
        )) . "\"\r\n");
}





// Now, start writing the transcript info.
fwrite($f, "\r\n\r\n" .
       '## Transcripts ## Do not remove or alter this header ##' . "\r\n" .
        '{{' . implode("}}\t{{", $aLOVDTranscriptColumns) . "}}\r\n");
$nTranscript = 0;
foreach ($aTranscriptsForLOVD as $aTranscript) {
    $nTranscript ++;
    fwrite($f, '"' . str_pad($nTranscript, 5, '0', STR_PAD_LEFT) . '"' . "\t\"" . implode("\"\t\"", $aTranscript) . "\"\r\n");
}
fclose($f);
print("\n"  .
      'All done, in ' . round(microtime(true) - $tStart) . ' seconds.');
?>
