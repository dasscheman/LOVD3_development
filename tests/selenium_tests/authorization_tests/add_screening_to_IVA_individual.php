<?php
class Example extends PHPUnit_Extensions_SeleniumTestCase
{
  protected function setUp()
  {
    $this->setBrowser("*chrome");
    $this->setBrowserUrl("https://localhost/");
  }

  public function testMyTestCase()
  {
    $this->open("/svn/LOVD3/trunk/src/submit/individual/00000001");
    $this->click("//div/table/tbody/tr/td/table/tbody/tr[2]/td[2]/b");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/screenings[\s\S]create&target=00000001$/',$this->getLocation()));
    $this->addSelection("name=Screening/Template[]", "label=RNA (cDNA)");
    $this->addSelection("name=Screening/Template[]", "label=Protein");
    $this->addSelection("name=Screening/Technique[]", "label=array for Comparative Genomic Hybridisation");
    $this->addSelection("name=Screening/Technique[]", "label=array for resequencing");
    $this->addSelection("name=Screening/Technique[]", "label=array for SNP typing");
    $this->addSelection("name=genes[]", "label=IVD (isovaleryl-CoA dehydrogenase)");
    $this->check("name=variants_found");
    $this->select("name=owned_by", "label=Test Owner");
    $this->click("//input[@value='Create screening information entry']");
    $this->waitForPageToLoad("30000");
    $this->assertEquals("Successfully created the screening entry!", $this->getText("css=table[class=info]"));
  }
}
?>