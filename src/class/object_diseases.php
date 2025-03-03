<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2010-07-28
 * Modified    : 2015-12-08
 * For LOVD    : 3.0-15
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

// Don't allow direct access.
if (!defined('ROOT_PATH')) {
    exit;
}
// Require parent class definition.
require_once ROOT_PATH . 'class/objects.php';





class LOVD_Disease extends LOVD_Object {
    // This class extends the basic Object class and it handles the Link object.
    var $sObject = 'Disease';





    function __construct ()
    {
        // Default constructor.
        global $_AUTH;

        // SQL code for loading an entry for an edit form.
        $this->sSQLLoadEntry = 'SELECT d.*, ' .
                               'GROUP_CONCAT(g2d.geneid ORDER BY g2d.geneid SEPARATOR ";") AS _genes ' .
                               'FROM ' . TABLE_DISEASES . ' AS d ' .
                               'LEFT OUTER JOIN ' . TABLE_GEN2DIS . ' AS g2d ON (d.id = g2d.diseaseid) ' .
                               'WHERE d.id = ? ' .
                               'GROUP BY d.id';

        // SQL code for viewing an entry.
        $this->aSQLViewEntry['SELECT']   = 'd.*, ' .
                                           '(SELECT COUNT(*) FROM ' . TABLE_INDIVIDUALS . ' AS i INNER JOIN ' . TABLE_IND2DIS . ' AS i2d ON (i.id = i2d.individualid) WHERE i2d.diseaseid = d.id' . ($_AUTH['level'] >= LEVEL_COLLABORATOR? '' : ' AND i.statusid >= ' . STATUS_MARKED) . ') AS individuals, ' .
                                           '(SELECT COUNT(*) FROM ' . TABLE_PHENOTYPES . ' AS p WHERE p.diseaseid = d.id' . ($_AUTH['level'] >= LEVEL_COLLABORATOR? '' : ' AND p.statusid >= ' . STATUS_MARKED) . ') AS phenotypes, ' .
                                           'GROUP_CONCAT(DISTINCT g2d.geneid ORDER BY g2d.geneid SEPARATOR ";") AS _genes, ' .
                                           'uc.name AS created_by_, ' .
                                           'ue.name AS edited_by_';
        $this->aSQLViewEntry['FROM']     = TABLE_DISEASES . ' AS d ' .
                                           'LEFT OUTER JOIN ' . TABLE_GEN2DIS . ' AS g2d ON (d.id = g2d.diseaseid) ' .
                                           'LEFT OUTER JOIN ' . TABLE_USERS . ' AS uc ON (d.created_by = uc.id) ' .
                                           'LEFT OUTER JOIN ' . TABLE_USERS . ' AS ue ON (d.edited_by = ue.id)';
        $this->aSQLViewEntry['GROUP_BY'] = 'd.id';

        // SQL code for viewing a list of entries.
        $this->aSQLViewList['SELECT']   = 'd.*, d.id AS diseaseid, ' .
                                          '(SELECT COUNT(DISTINCT i.id) FROM ' . TABLE_IND2DIS . ' AS i2d LEFT OUTER JOIN ' . TABLE_INDIVIDUALS . ' AS i ON (i2d.individualid = i.id' . ($_AUTH['level'] >= LEVEL_COLLABORATOR? '' : ' AND i.statusid >= ' . STATUS_MARKED) . ') WHERE i2d.diseaseid = d.id) AS individuals, ' .
                                          '(SELECT COUNT(*) FROM ' . TABLE_PHENOTYPES . ' AS p WHERE p.diseaseid = d.id' . ($_AUTH['level'] >= LEVEL_COLLABORATOR? '' : ' AND p.statusid >= ' . STATUS_MARKED) . ') AS phenotypes, ' .
                                          'COUNT(g2d.geneid) AS gene_count, ' .
                                          'GROUP_CONCAT(DISTINCT g2d.geneid ORDER BY g2d.geneid SEPARATOR ";") AS _genes';
        $this->aSQLViewList['FROM']     = TABLE_DISEASES . ' AS d ' .
                                          'LEFT OUTER JOIN ' . TABLE_GEN2DIS . ' AS g2d ON (d.id = g2d.diseaseid)';
        $this->aSQLViewList['WHERE']    = 'd.id > 0';
        $this->aSQLViewList['GROUP_BY'] = 'd.id';

        // List of columns and (default?) order for viewing an entry.
        $this->aColumnsViewEntry =
                 array(
                        'symbol' => 'Official abbreviation',
                        'name' => 'Name',
                        'id_omim' => 'OMIM ID',
                        'individuals' => 'Individuals reported having this disease',
                        'phenotypes_' => 'Phenotype entries for this disease',
                        'genes_' => 'Associated with',
                        'created_by_' => array('Created by', LEVEL_COLLABORATOR),
                        'created_date_' => array('Date created', LEVEL_COLLABORATOR),
                        'edited_by_' => array('Last edited by', LEVEL_COLLABORATOR),
                        'edited_date_' => array('Date last edited', LEVEL_COLLABORATOR),
                      );

        // List of columns and (default?) order for viewing a list of entries.
        $this->aColumnsViewList =
                 array(
                        'diseaseid' => array(
                                    'view' => array('ID', 45),
                                    'db'   => array('d.id', 'ASC', true)),
                        'symbol' => array(
                                    'view' => array('Abbreviation', 110),
                                    'db'   => array('d.symbol', 'ASC', true)),
                        'name' => array(
                                    'view' => array('Name', 300),
                                    'db'   => array('d.name', 'ASC', true)),
                        'id_omim' => array(
                                    'view' => array('OMIM ID', 75),
                                    'db'   => array('d.id_omim', 'ASC', true)),
                        'individuals' => array(
                                    'view' => array('Individuals', 80, 'style="text-align : right;"'),
                                    'db'   => array('individuals', 'DESC', 'INT_UNSIGNED')),
                        'phenotypes' => array(
                                    'view' => array('Phenotypes', 80, 'style="text-align : right;"'),
                                    'db'   => array('phenotypes', 'DESC', 'INT_UNSIGNED')),
                        'genes_' => array(
                                    'view' => array('Associated with genes', 200),
                                    'db'   => array('_genes', false, 'TEXT')),
                      );
        $this->sSortDefault = 'symbol';

        // Because the disease information is publicly available, remove some columns for the public.
        $this->unsetColsByAuthLevel();

        parent::__construct();
    }





    function checkFields ($aData, $zData = false)
    {
        // Checks fields before submission of data.
        global $_AUTH, $_DB;

        $bImport = (lovd_getProjectFile() == '/import.php');
        $bCreate = ((ACTION && ACTION == 'create') || ($bImport && !$zData));

        // Mandatory fields.
        $this->aCheckMandatory =
                 array(
                        'symbol',
                        'name',
                      );
        $aData = parent::checkFields($aData);

        if (!empty($aData['id_omim']) && !preg_match('/^[1-9]\d{5}$/', $aData['id_omim'])) {
            lovd_errorAdd('id_omim', 'The OMIM ID has to be six digits long and cannot start with a \'0\'.');
        }
        // Two diseases with the same OMIM ID are not allowed.
        if (!empty($aData['id_omim']) && ($bCreate || $aData['id_omim'] != $zData['id_omim'])) {
            $bExists = $_DB->query('SELECT id FROM ' . TABLE_DISEASES . ' WHERE id_omim = ?', array($aData['id_omim']))->fetchColumn();
            if ($bExists) {
                // IMPORTANT: when you change this message, also change the array_search argument in import.php in the Disease section.
                lovd_errorAdd('id_omim', 'Another disease already exists with this OMIM ID!');
            }
        }
        // We don't like two diseases with the exact same name, either.
        if (!empty($aData['name']) && ($bCreate || $aData['name'] != $zData['name'])) {
            $bExists = $_DB->query('SELECT id FROM ' . TABLE_DISEASES . ' WHERE name = ?', array($aData['name']))->fetchColumn();
            if ($bExists && ($bCreate || $zData['id'] != $bExists)) {
                // IMPORTANT: when you change this message, also change the array_search argument in import.php in the Disease section.
                lovd_errorAdd('name', 'Another disease already exists with the same name!');
            }
        }

        if (!$bImport && $_AUTH['level'] < LEVEL_MANAGER && empty($aData['genes'])) {
            lovd_errorAdd('genes', 'You should at least select one of the genes you are curator of.');
        }

        $_POST['genes'] = array();
        if (is_array($aData['genes'])) {
            foreach ($aData['genes'] as $sGene) {
                if (!lovd_isAuthorized('gene', $sGene, false) && $bCreate) {
                    lovd_errorAdd('genes', 'You are not authorized to add this disease to gene ' . htmlspecialchars($sGene) . '.');
                } else {
                    $_POST['genes'][] = $sGene;
                }
            }
        }
        if (!$bCreate) {
            if (is_array($aData['genes']) && isset($zData['genes']) && is_array($zData['genes'])) {
                foreach ($zData['genes'] as $sGene) {
                    if ($sGene && !in_array($sGene, $aData['genes']) && !lovd_isAuthorized('gene', $sGene, false)) {
                        lovd_errorAdd('genes', 'You are not authorized to remove this disease from gene ' . htmlspecialchars($sGene) . '.');
                        $_POST['genes'][] = $sGene;
                    }
                }
            }
        }

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

        global $_DB, $_AUTH;

        // Get list of genes, to connect disease to gene.
        if ($_AUTH['level'] == LEVEL_CURATOR) {
            $aGenes = $_AUTH['curates'];
            if (ACTION == 'edit') {
                global $zData;
                $aGenes = array_unique(array_merge($aGenes, $zData['genes']));
            }
            $aGenesForm = $_DB->query('SELECT id, name FROM ' . TABLE_GENES . ' WHERE id IN (?' . str_repeat(', ?', count($aGenes) - 1) . ') ORDER BY id', $aGenes)->fetchAllCombine();
        } else {
            $aGenesForm = $_DB->query('SELECT id, name FROM ' . TABLE_GENES . ' ORDER BY id')->fetchAllCombine();
        }
        $nData = count($aGenesForm);
        foreach ($aGenesForm as $sID => $sGene) {
            $aGenesForm[$sID] = $sID . ' (' . lovd_shortenString($sGene, 50) . ')';
        }
        if (!$nData) {
            $aGenesForm = array('' => 'No gene entries available');
        }
        $nFieldSize = (count($aGenesForm) < 15? count($aGenesForm) : 15);

        // Array which will make up the form table.
        $this->aFormData =
                 array(
                        array('POST', '', '', '', '50%', '14', '50%'),
                        array('', '', 'print', '<B>Disease information</B>'),
                        'hr',
                        array('Disease abbreviation', '', 'text', 'symbol', 15),
                        array('Disease name', '', 'text', 'name', 40),
                        array('OMIM ID (optional)', '', 'text', 'id_omim', 10),
                        'hr',
                        'skip',
                        array('', '', 'print', '<B>Relation to genes (optional)</B>'),
                        'hr',
            'aGenes' => array('This disease has been linked to these genes', '', 'select', 'genes', $nFieldSize, $aGenesForm, false, true, false),
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
            $zData['row_id'] = $zData['id'];
            $zData['symbol'] = '<A href="' . $zData['row_link'] . '" class="hide">' . $zData['symbol'] . '</A>';
            $zData['genes_'] = '';
            $i = 0;
            if (count($zData['genes']) > 22) {
                // Don't show all genes.
                foreach ($zData['genes'] as $key => $sID) {
                    $zData['genes_'] .= (!$key? '' : ', ') . $sID;
                    $i++;
                    if ($i >= 20) {
                        break;
                    }
                }
                $zData['genes_'] .= ', ' . ($zData['gene_count'] - $i) . ' more';
            } else {
                $zData['genes_'] = implode(', ', $zData['genes']);
            }
        } else {
            if (!empty($zData['id_omim'])) {
                $zData['id_omim'] = '<A href="' . lovd_getExternalSource('omim', $zData['id_omim'], true) . '" target="_blank">' . $zData['id_omim'] . '</A>';
            }
            $zData['phenotypes_'] = $zData['phenotypes'];
            if ($zData['phenotypes']) {
                $zData['phenotypes_'] = '<A href="phenotypes/disease/' . $zData['id'] . '">' . $zData['phenotypes'] . '</A>';
            }
            // Provide links to gene symbols this disease is associated with.
            $this->aColumnsViewEntry['genes_'] .= ' ' . count($zData['genes']) . ' gene' . (count($zData['genes']) == 1? '' : 's');
            $zData['genes_'] = '';
            $zData['genes_short_'] = '';
            $i = 0;
            foreach ($zData['genes'] as $key => $sID) {
                $zData['genes_'] .= (!$key? '' : ', ') . '<A href="genes/' . $sID . '">' . $sID . '</A>';
                if ($i < 20) {
                    $zData['genes_short_'] .= (!$key? '' : ', ') . '<A href="genes/' . $sID . '">' . $sID . '</A>';
                    $i++;
                }
            }
            if (count($zData['genes']) > 22) {
                // Replace long gene list by shorter one, allowing expand.
                $zData['genes_'] = '<SPAN>' . $zData['genes_short_'] . ', <A href="#" onclick="$(this).parent().hide(); $(this).parent().next().show(); return false;">' . (count($zData['genes']) - $i) . ' more...</A></SPAN><SPAN style="display : none;">' . $zData['genes_'] . '</SPAN>';
            }
        }

        return $zData;
    }
}
?>
