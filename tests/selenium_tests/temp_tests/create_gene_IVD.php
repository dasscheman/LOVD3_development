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
    $this->open("/svn/LOVD3/trunk/src/logout");
    $this->open("/svn/LOVD3/trunk/src/login");
    $this->type("name=username", "admin");
    $this->type("name=password", "test1234");
    $this->click("//input[@value='Log in']");
    $this->waitForPageToLoad("30000");
    $this->open("/svn/LOVD3/trunk/src/genes?create");
    $this->type("name=hgnc_id", "IVD");
    $this->click("//input[@value='Continue »']");
    $this->waitForPageToLoad("30000");
    $this->addSelection("name=active_transcripts[]", "label=transcript variant 1 (NM_002225.3)");
    $this->click("name=show_hgmd");
    $this->click("name=show_genecards");
    $this->click("name=show_genetests");
    $this->click("//input[@value='Create gene information entry']");
    $this->waitForPageToLoad("30000");
    $this->assertEquals("Successfully created the gene information entry!", $this->getText("css=table[class=info]"));
  }
}
?>