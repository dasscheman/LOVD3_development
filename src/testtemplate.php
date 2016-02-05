<?php
/*******************************************************************************
 *
 * 
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2012-03-27
 * Modified    : 2015-11-25
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

define('ROOT_PATH', './');
require ROOT_PATH . 'inc-init.php';


$qGenes = $_DB->query('SELECT id, id as value, CONCAT(id, " (", name, ")") AS label FROM ' . TABLE_GENES . ' WHERE id = ?', array('ARSE'));
$zGenes = $qGenes->fetchAllAssoc(); 

if (empty($zGenes)) {
    echo "die(AJAX_DATA_ERROR)";
}
var_dump($zGenes, empty($zGenes)); exit;

/*
// global $_AUTH, $_CONF, $_DB, $_SETT, $_STAT;
    lovd_includeJS('inc-js-openwindow.php', 1);
    lovd_includeJS('inc-js-toggle-visibility.js', 1); // Used on forms and variant overviews for small info tables.
    lovd_includeJS('lib/jQuery/jquery.min.js', 1);
    lovd_includeJS('lib/jQuery/jquery-ui.custom.min.js', 1);
    lovd_includeJS('lib/jeegoocontext/jquery.jeegoocontext.min.js', 1);
    
    $qGenes = $_DB->query('SELECT id, id as value, CONCAT(id, " (", name, ")") AS label FROM ' . TABLE_GENES . ' ORDER BY id');
    $availableGenes = json_encode($qGenes->fetchAllAssoc());
    $sGeneSwitchURL = '';
    if (!empty($_SESSION['currdb'])) {
        $sGeneSwitchURL = preg_replace('/(\/)' . preg_quote($_SESSION['currdb'], '/') . '\b/', "$1{{GENE}}", $_SERVER['REQUEST_URI']);
    }
    var_dump($sGeneSwitchURL, $_GET);
    
    if (!is_readable(ROOT_PATH . $_CONF['logo_uri'])) {
        $_CONF['logo_uri'] = 'gfx/LOVD3_logo145x50.jpg';
    }
    $aImage = @getimagesize(ROOT_PATH . $_CONF['logo_uri']);
    if (!is_array($aImage)) {
        $aImage = array('130', '50', '', 'width="130" heigth="50"');
    }
    list($nWidth, $nHeight, $sType, $sSize) = $aImage;
    
    $sCurrSymbol = $sCurrGene = '';
    if (!empty($_SESSION['currdb'])) {
        // Just use currently selected database.
        $sCurrSymbol = $_SESSION['currdb'];
        $sCurrGene = $_SETT['currdb']['name'];
    }
   // var_dump($_SESSION, $_SETT); 
?>

<HEAD>
    <TITLE><?php echo (!defined('PAGE_TITLE')? '' : PAGE_TITLE . ' - ') . $_CONF['system_title']; ?></TITLE>
    <META http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <META name="author" content="LOVD development team, LUMC, Netherlands">
    <META name="generator" content="gPHPEdit / GIMP @ GNU/Linux (Ubuntu)">
    <BASE href="<?php echo lovd_getInstallURL(); ?>">
    <LINK rel="stylesheet" type="text/css" href="styles.css">
    <LINK rel="stylesheet" type="text/css" href="lib/jeegoocontext/style.css">
    <LINK rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</HEAD>
<BODY style="margin : 10px;">

<TABLE border="0" cellpadding="0" cellspacing="0" width="100%">
    <LINK rel="stylesheet" type="text/css" href="lib/jQuery/css/cupertino/jquery-ui.custom.css">
    <TD valign="top" width="<?php echo $nWidth ?>" height="<?php echo $nHeight ?>">
        <IMG src="<?php echo $_CONF['logo_uri']; ?>" alt="LOVD - Leiden Open Variation Database <?php echo $sSize ?>">
    </TD>  

    <TD valign="top" style="padding-top : 2px;">
        <H2 style="margin-bottom : 2px;"> <?php $_CONF['system_title'] ?></H2>
        <?php
        if ($sCurrSymbol && $sCurrGene) { ?>
            <H5 id="gene_name"> <?php $sCurrGene . ' (' . $sCurrSymbol . ')' ;?> </H5> <?php 
        }?>
        <A href="#" onclick="lovd_switchGene(); return false;">
            <IMG src="gfx/lovd_genes_switch_inline.png" width="23" height="23" alt="Switch gene" title="Switch gene database" align="top">
        </A>
       
        <FORM action="" id="SelectGeneDBInline" method="get" style="margin : 0px;" onsubmit="document.location.href=(sURL.replace($(this).children('select').val())); return false;">
            <DIV class="select_gene_dropdown">
                <LABEL for="select_gene_dropdown">Select Gene: </LABEL>
                <SELECT name="select_db_dropdown" id="select_gene_dropdown" onchange="$(this).parent().submit();">
                </SELECT>
            </DIV>

            <DIV class="select_gene_autocomplete">
                <LABEL for="select_gene_autocomplete">Select Gene: </LABEL>
                <INPUT name="select_db_autocomplete" id="select_gene_autocomplete" onchange="$(this).parent().submit();">
            </DIV>
            <INPUT type="submit" value="Switch" name="submit" class="select_gene_switch">
        </FORM>
    </TD>
</TABLE> 

<SCRIPT type="text/javascript">
    var items="";
    var availableGenes = <?php echo $availableGenes; ?>;

    $(function() {
        $(".select_gene_dropdown").hide();            
        $(".select_gene_autocomplete").hide();     
        $(".select_gene_switch").hide();
    });

    function lovd_getAutocompleteList() {
        $( "#select_gene_autocomplete" ).autocomplete({
            source: availableGenes,
            minLength: 3
        });
    };

    function lovd_getDropdownList(){    
        $.each(availableGenes,function(index,item) 
        {
            items+="<option value='"+item.id+"'>"+item.label+"</option>";
        });
        $("#select_gene_dropdown").html(items); 
    };

    function lovd_switchGene () {
        var sURL = "<?php if (!empty($_SESSION['currdb'])) {echo $sGeneSwitchURL;} ?>";
        if (availableGenes.length > 10) {
            lovd_getDropdownList();
            $(".select_gene_dropdown").show();            
        } else {
            lovd_getAutocompleteList();  
            $(".select_gene_autocomplete").show();       
        }
        $(".select_gene_switch").show();
    }
    
</SCRIPT>



        
        if ($sCurrSymbol && $sCurrGene) { ?>
            <!--Javascript function lovd_switchGene hide <H5 id=gene_name> when 
                the user hits the gene switch.-->
            <H5 id="gene_name" style="display:inline"> <?php echo $sCurrGene . ' (' . $sCurrSymbol . ')';?> </H5> 
            <?php if (strpos($sGeneSwitchURL, '{{GENE}}') !== false) { echo "\n"?>
                <H5 id="gene_switch" style="display:inline">              
                    <A href="#" onclick="lovd_switchGene(); return false;">
                        <IMG src="gfx/lovd_genes_switch_inline.png" width="23" height="23" alt="Switch gene" title="Switch gene database" align="top">
                    </A>
                </H5>
            <?php }
        } ?>
        
        <FORM action="" id="SelectGeneDBInline" method="get" style="margin : 0px;" onsubmit="lovd_changeURL(); return false;">
            <!--By default both DIVs div_gene_dropdown and div_gene_autocomplete
                are hidden. Id is used to make the DIV visible with javascript 
                function lovd_switchGene()-->
            <DIV id="div_gene_dropdown" style="display:none">
                <SELECT name="select_db" id="select_gene_dropdown" onchange="$(this).parent().submit();"></SELECT>
                <INPUT type="submit" value="Switch" id="select_gene_switch">
            </DIV>

            <DIV id="div_gene_autocomplete" style="display:none">
                <INPUT name="select_db" id="select_gene_autocomplete" onchange="$(this).parent().submit();">
                <INPUT type="submit" value="Switch" id="select_gene_switch">
            </DIV>
        </FORM>*/