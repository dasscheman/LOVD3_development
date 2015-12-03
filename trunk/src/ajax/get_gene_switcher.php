<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2015-11-27
 * Modified    : 2015-11-27
 * For LOVD    : 3.0-15
 *
 * Copyright   : 2004-2015 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmer  : Msc. Daan Asscheman <D.Asscheman@LUMC.nl>
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
session_write_close();

$sGeneSwitchURL = '';

if (!empty($_GET['sGeneSwitchURL'])) {
    $sGeneSwitchURL = $_GET['sGeneSwitchURL'];
}

$qGenes = $_DB->query('SELECT id, id as value, CONCAT(id, " (", name, ")") AS label FROM ' . TABLE_GENES . ' ORDER BY id');
//$qGenes = $_DB->query('SELECT id, id as value, CONCAT(id, " (", name, ")") AS label FROM ' . TABLE_GENES . ' WHERE id = ?', array('ARSE'));
$zGenes = $qGenes->fetchAllAssoc(); 

if (empty($zGenes)) {
    die(AJAX_DATA_ERROR);
}

foreach($zGenes as $key=>$value) {
    //This will shorten the gene names nicely, to prevent long gene names from messing up the form.
    $zGenes[$key]['label'] = lovd_shortenString($zGenes[$key]['label'], 75);
}

//die(json_encode($zGenes));
?>

<SCRIPT type="text/javascript">
    var availableGenes= '<?php echo (empty($zGenes)? '' : json($zGenes)) ?>';
    var maxDropDown = 10;
        
    $(function() {
        $("#gene_name").hide(); 
        $("#gene_switch").hide(); 
        if (availableGenes.length < maxDropDown) {
            lovd_getDropdownList();
            $("#div_gene_dropdown").show();            
        } else {
            lovd_getAutocompleteList();  
            $("#div_gene_autocomplete").show();       
        }
        $("select_gene_switch").show();
    });

    function lovd_getAutocompleteList() {
        $( "#select_gene_autocomplete" ).autocomplete({
            source: availableGenes,
            minLength: 3
        });
    };

    function lovd_getDropdownList(){          
        var items="";
        $.each(availableGenes,function(index,item) 
        {
            items+="<option value='"+item.id+"'>"+item.label+"</option>";
        });
        $("#select_gene_dropdown").html(items); 
    };

  /*  function lovd_switchGene(){ 
        availableGenes = <?php echo $zGenes ?>;
        $("#gene_name").hide(); 
        $("#gene_switch").hide(); 
        if (availableGenes.length < maxDropDown) {
            lovd_getDropdownList();
            $("#div_gene_dropdown").show();            
        } else {
            lovd_getAutocompleteList();  
            $("#div_gene_autocomplete").show();       
        }
        $("select_gene_switch").show();
    }*/
    
    function lovd_changeURL () {
        var sURL = "<?php if (!empty($_SESSION['currdb'])) {echo $sGeneSwitchURL;} ?>";
        if (availableGenes.length < maxDropDown) {         
            document.location.href = (sURL.replace('{{GENE}}', document.getElementById('select_gene_dropdown').value));
        } else { 
            document.location.href = (sURL.replace('{{GENE}}', document.getElementById('select_gene_autocomplete').value));
        }
    }
</SCRIPT>
<LINK rel="stylesheet" type="text/css" href="lib/jQuery/css/cupertino/jquery-ui.custom.css">

<?php

print('      <FORM action="" id="SelectGeneDBInline" method="get" style="margin : 0px;" onsubmit="lovd_changeURL(); return false;">' . "\n" .
      '        <!--By default both DIVs div_gene_dropdown and div_gene_autocomplete are hidden. Id is used to make the DIV visible with javascript function lovd_switchGene()-->' . "\n" .
      '        <DIV id="div_gene_dropdown" style="display:none">' . "\n" .
      '          <SELECT name="select_db" id="select_gene_dropdown" onchange="$(this).parent().submit();"></SELECT>' . "\n" .
      '          <INPUT type="submit" value="Switch" id="select_gene_switch">' . "\n" .
      '        </DIV>' . "\n" .
      '        <DIV id="div_gene_autocomplete" style="display:none">' . "\n" .
      '          <INPUT name="select_db" id="select_gene_autocomplete" onchange="$(this).parent().submit();">' . "\n" .
      '          <INPUT type="submit" value="Switch" id="select_gene_switch">' . "\n" .
      '        </DIV>' . "\n" .
      '      </FORM>');