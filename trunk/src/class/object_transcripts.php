<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2010-12-20
 * Modified    : 2013-09-11
 * For LOVD    : 3.0-08
 *
 * Copyright   : 2004-2013 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmers : Ing. Ivar C. Lugtenburg <I.C.Lugtenburg@LUMC.nl>
 *               Ing. Ivo F.A.C. Fokkema <I.F.A.C.Fokkema@LUMC.nl>
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

// Don't allow direct access.
if (!defined('ROOT_PATH')) {
    exit;
}
// Require parent class definition.
require_once ROOT_PATH . 'class/objects.php';





class LOVD_Transcript extends LOVD_Object {
    // This class extends the basic Object class and it handles the Link object.
    var $sObject = 'Transcript';





    function __construct ()
    {
        // Default constructor.

        // SQL code for loading an entry for an edit form.
        $this->sSQLLoadEntry = 'SELECT t.* ' .
                               'FROM ' . TABLE_TRANSCRIPTS . ' AS t ' .
                               'WHERE id = ?';

        // SQL code for viewing an entry.
        $this->aSQLViewEntry['SELECT']   = 't.*, ' .
                                           'g.name AS gene_name, ' .
                                           'g.chromosome, ' .
                                           'uc.name AS created_by_, ' .
                                           'ue.name AS edited_by_, ' .
                                           'COUNT(DISTINCT vot.id) AS variants';
        $this->aSQLViewEntry['FROM']     = TABLE_TRANSCRIPTS . ' AS t ' .
                                           'LEFT OUTER JOIN ' . TABLE_GENES . ' AS g ON (t.geneid = g.id) ' .
                                           'LEFT OUTER JOIN ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' AS vot ON (t.id = vot.transcriptid) ' .
                                           'LEFT OUTER JOIN ' . TABLE_USERS . ' AS uc ON (t.created_by = uc.id) ' .
                                           'LEFT OUTER JOIN ' . TABLE_USERS . ' AS ue ON (t.edited_by = ue.id)';
        $this->aSQLViewEntry['GROUP_BY'] = 't.id';

        // SQL code for viewing the list of transcripts
        $this->aSQLViewList['SELECT']   = 't.*, ' .
                                          'g.chromosome, ' .
                                          'COUNT(DISTINCT vot.id) AS variants';
        $this->aSQLViewList['FROM']     = TABLE_TRANSCRIPTS . ' AS t ' .
                                          'LEFT OUTER JOIN ' . TABLE_GENES . ' AS g ON (t.geneid = g.id) ' .
                                          'LEFT OUTER JOIN ' . TABLE_VARIANTS_ON_TRANSCRIPTS . ' AS vot ON (t.id = vot.transcriptid)';
        $this->aSQLViewList['GROUP_BY'] = 't.id';

        // List of columns and (default?) order for viewing an entry.
        $this->aColumnsViewEntry =
                 array(
                        'name' => 'Transcript name',
                        'gene_name_' => 'Gene name',
                        'chromosome' => 'Chromosome',
                        'id_ncbi_' => 'Transcript - NCBI ID',
                        'id_ensembl' => 'Transcript - Ensembl ID',
                        'id_protein_ncbi' => 'Protein - NCBI ID',
                        'id_protein_ensembl' => 'Protein - Ensembl ID',
                        'id_protein_uniprot' => 'Protein - Uniprot ID',
                        'exon_table' => 'Exon/intron information',
                        'created_by_' => array('Created by', LEVEL_COLLABORATOR),
                        'created_date_' => array('Date created', LEVEL_COLLABORATOR),
                        'edited_by_' => array('Last edited by', LEVEL_COLLABORATOR),
                        'edited_date_' => array('Date last edited', LEVEL_COLLABORATOR),
                      );

        // List of columns and (default?) order for viewing a list of entries.
        $this->aColumnsViewList =
            array(
                'id_' => array(
                    'view' => array('ID', 70),
                    'db'   => array('t.id', 'ASC', true)),
                'chromosome' => array(
                    'view' => array('Chr', 40),
                    'db'   => array('g.chromosome', 'ASC', true)),
                'geneid' => array(
                    'view' => array('Gene ID', 100),
                    'db'   => array('t.geneid', 'ASC', true)),
                'name' => array(
                    'view' => array('Name', 300),
                    'db'   => array('t.name', 'ASC', true)),
                'id_ncbi' => array(
                    'view' => array('NCBI ID', 120),
                    'db'   => array('t.id_ncbi', 'ASC', true)),
                'id_protein_ncbi' => array(
                    'view' => array('NCBI Protein ID', 120),
                    'db'   => array('t.id_protein_ncbi', 'ASC', true)),
                'variants' => array(
                    'view' => array('Variants', 70),
                    'db'   => array('variants', 'DESC', 'INT_UNSIGNED')),
            );
        $this->sSortDefault = 'geneid';

        // Because the disease information is publicly available, remove some columns for the public.
        $this->unsetColsByAuthLevel();

        parent::__construct();
    }





    function checkFields ($aData, $zData = false)
    {
        // Checks fields before submission of data.

        parent::checkFields($aData);

        // XSS attack prevention. Deny input of HTML.
        lovd_checkXSS();
    }





    function getForm ()
    {
        // Build the form.

        // If we've built the form before, simply return it. Especially imports will repeatedly call checkFields(), which calls getForm().
        if (!empty($this->aFormData)) {
            return parent::getForm();
        }

        // Array which will make up the form table.
        $this->aFormData =
                 array(
                           array('POST', '', '', '', '40%', '14', '60%'),
                           array('', '', 'print', '<B>General information</B>'),
                           'hr',
                           array('Transcript Ensembl ID', '', 'text', 'id_ensembl', 10),
                           array('Protein Ensembl ID', '', 'text', 'id_protein_ensembl', 10),
                           array('Protein Uniprot ID', '', 'text', 'id_protein_uniprot', 10),
                           'hr',
                           'skip',
                  );

        return parent::getForm();
    }





    function prepareData ($zData = '', $sView = 'list')
    {
        // Prepares the data by "enriching" the variable received with links, pictures, etc.

        if (!in_array($sView, array('list', 'entry'))) {
            $sView = 'list';
        }

        // Makes sure it's an array and htmlspecialchars() all the values.
        $zData = parent::prepareData($zData, $sView);

        if ($sView == 'list') {
            $zData['id_'] = '<A href="' . str_replace('{{ID}}', $zData['id'], $this->sRowLink) . '" class="hide">' . $zData['id'] . '</A>';
        } else {
            $zData['gene_name_'] = '<A href="genes/' . rawurlencode($zData['geneid']) . '">' . $zData['geneid'] . '</A> (' . $zData['gene_name'] . ')';
            $zData['id_ncbi_'] = '<A href="http://www.ncbi.nlm.nih.gov/nuccore/' . $zData['id_ncbi'] . '" target="_blank">'  . $zData['id_ncbi'] . '</A>';

            // Exon/intron info table. Check if files exist, and build link. Otherwise, remove field.
            $sExonTable = '';
            $sExonTableFileHTML = ROOT_PATH . 'refseq/' . $zData['geneid'] . '_' . $zData['id_ncbi'] . '_table.html';
            $sExonTableFileTXT = ROOT_PATH . 'refseq/' . $zData['geneid'] . '_' . $zData['id_ncbi'] . '_table.txt';
            if (is_readable($sExonTableFileHTML)) {
                $sExonTable .= (!$sExonTable? '' : ', ') . '<A href="' . $sExonTableFileHTML . '" target="_blank">HTML</A>';
            }
            if (is_readable($sExonTableFileTXT)) {
                $sExonTable .= (!$sExonTable? '' : ', ') . '<A href="' . $sExonTableFileTXT . '" target="_blank">Txt</A>';
            }
            if ($sExonTable) {
                $zData['exon_table'] = 'Exon/intron information table: ' . $sExonTable;
            } else {
                unset($this->aColumnsViewEntry['exon_table']);
            }
        }

        return $zData;
    }





    /**
     * This method returns transcripts and info from mutalyzer.
     * @param string $sRefseqUD Search for variant which are on this cromosome.
     * @param string $sSymbol Array with start and end positions of the transcripts.
     * @return $aTranscriptInfo
     **/
    public function getTranscriptPositions($sRefseqUD, $sSymbol, $sGeneName, &$nProgress = 0.0)
    {
        global $_BAR, $_SETT;

        $_Mutalyzer = new LOVD_SoapClient();
        $aTranscripts['id'] = array();
        $aTranscripts['name'] = array();
        $aTranscripts['mutalyzer'] = array();
        $aTranscripts['positions'] = array();
        $aTranscripts['protein'] = array();
        $aTranscripts['added'] = array();

        $sGeneSymbol = $sSymbol;

        if (in_array($sSymbol, array_keys($_SETT['mito_genes_aliases']))) {
            // For mitochandrial genes an alias must be used to get the transcripts and info.
            // List of aliasses are hard-coded in inc-init.php
            $sGeneSymbol = $_SETT['mito_genes_aliases'][$sSymbol];
        }

        try {
            // Can throw notice when TranscriptInfo is not present (when a gene recently has been renamed, for instance).
            $aTranscripts['info'] = @$_Mutalyzer->getTranscriptsAndInfo(array('genomicReference' => $sRefseqUD, 'geneName' => $sGeneSymbol))->getTranscriptsAndInfoResult->TranscriptInfo;
        } catch (SoapFault $e) {
            lovd_soapError($e);
        }
        if (empty($aTranscripts['info'])) {
            // No transcripts found.
            $aTranscripts['info'] = array();
            return $aTranscripts;
        }

        if (in_array($sSymbol, array_keys($_SETT['mito_genes_aliases']))) {
            //For Mitochondrial genes, we won't be able to get any proper transcript information. Fake one.
            $sRefseqNM = $sRefseqUD . '(' . $sSymbol . '_v001)';
            $aTranscripts['id'] = array($sRefseqNM);
            $aTranscripts['protein'] = array($sRefseqNM => '');
            // preg_replace is done in the original code for mutalyzer and name, cannot trace back why.
            // Not doing this will cause an error index not found transcripts.php.
            // TODO DAAN: Can not figure out why version is not included. Therefor for now we will do without.
            //$aTranscripts['mutalyzer'] = array(preg_replace('/\.\d+/', '', $sRefseqNM) => '001');
            //$aTranscripts['name'] = array(preg_replace('/\.\d+/', '', $sRefseqNM) => 'transcript variant 1'); // FIXME: Perhaps indicate this transcript is a fake one, reconstructed from the CDS?
            $aTranscripts['mutalyzer'] = array($sRefseqNM => '001');
            $aTranscripts['name'] = array($sRefseqNM => 'transcript variant 1'); // FIXME: Perhaps indicate this transcript is a fake one, reconstructed from the CDS?
            $aTranscripts['positions'] = array($sRefseqNM =>
                array(
                    // For mitochondrial genes we used the NC to get the transcriptInfo therefor we can use the gTransStart and gTransEnd.
                    'chromTransStart' => (isset($aTranscripts['info'][0]->gTransStart)? $aTranscripts['info'][0]->gTransStart : 0),
                    'chromTransEnd' => (isset($aTranscripts['info'][0]->gTransEnd)? $aTranscripts['info'][0]->gTransEnd : 0),
                    'cTransStart' => (isset($aTranscripts['info'][0]->cTransStart)? $aTranscripts['info'][0]->cTransStart : 0),
                    'cTransEnd' => (isset($aTranscripts['info'][0]->sortableTransEnd)? $aTranscripts['info'][0]->sortableTransEnd : 0),
                    'cCDSStop' => (isset($aTranscripts['info'][0]->cCDSStop)? $aTranscripts['info'][0]->cCDSStop : 0),
                )
            );
            return $aTranscripts;
        }

        $nTranscripts = count($aTranscripts['info']);
        foreach($aTranscripts['info'] as $oTranscript) {
            $nProgress += ((100 - $nProgress)/$nTranscripts);
            $_BAR->setMessage('Collecting ' . $oTranscript->id . ' info...');
            if ($oTranscript->id || in_array($sSymbol, array_keys($_SETT['mito_genes_aliases']))) {
                $aTranscripts['id'][] = $oTranscript->id;
                // TODO DAAN: Can not figure out why version is not included. Therefor for now we will do without.
                $aTranscripts['name'][$oTranscript->id] = str_replace($sGeneName . ', ', '', $oTranscript->product);
                $aTranscripts['mutalyzer'][$oTranscript->id] = str_replace($sSymbol . '_v', '', $oTranscript->name);
                //$aTranscripts['name'][preg_replace('/\.\d+/', '', $oTranscript->id)] = str_replace($sGeneName . ', ', '', $oTranscript->product);
                //$aTranscripts['mutalyzer'][preg_replace('/\.\d+/', '', $oTranscript->id)] = str_replace($sSymbol . '_v', '', $oTranscript->name);
                $aTranscripts['positions'][$oTranscript->id] =
                    array(
                        'chromTransStart' => (isset($oTranscript->chromTransStart)? $oTranscript->chromTransStart : 0),
                        'chromTransEnd' => (isset($oTranscript->chromTransEnd)? $oTranscript->chromTransEnd : 0),
                        'cTransStart' => $oTranscript->cTransStart,
                        'cTransEnd' => $oTranscript->sortableTransEnd,
                        'cCDSStop' => $oTranscript->cCDSStop,
                    );
                $aTranscripts['protein'][$oTranscript->id] = (!isset($oTranscript->proteinTranscript)? '' : $oTranscript->proteinTranscript->id);
            }
            $_BAR->setProgress($nProgress);
        }

        return $aTranscripts;
    }





    /**
     * This method turns off the MAPPING_DONE flag for a variant within the range of a transcript.
     * Automatic mapping will pick them up again.
     * @param string $sChromosome Search for variant which are on this cromosome.
     * @param array $aTranscriptPositions Array with start and end positions of the transcripts.
     * @return
     **/
    public function turnOffMappingDone($sChromosome, $aTranscriptPositions)
    {
        global $_DB;

        $q = $_DB->query('UPDATE ' . TABLE_VARIANTS . '
                         SET mapping_flags = mapping_flags & ~' . MAPPING_DONE . '
                         WHERE chromosome = ?
                         AND (position_g_start BETWEEN ? AND ?)
                         OR (position_g_end   BETWEEN ? AND ?)
                         OR (position_g_start < ? AND position_g_end > ?)',
                         array($sChromosome,
                               $aTranscriptPositions['chromTransStart'],
                               $aTranscriptPositions['chromTransEnd'],
                               $aTranscriptPositions['chromTransStart'],
                               $aTranscriptPositions['chromTransEnd'],
                               $aTranscriptPositions['chromTransStart'],
                               $aTranscriptPositions['chromTransEnd']));
        if ($q->rowCount()) {
            // If we have changed variants, turn on mapping immediately.
            $_SESSION['mapping']['time_complete'] = 0;
        }
    }
}
?>
