<?php
class Example extends PHPUnit_Extensions_SeleniumTestCase
{
  protected function setUp()
  {
    $this->setBrowser("*chrome");
    $this->setBrowserUrl("https://localhost/svn/LOVD3/trunk/src/install/");
  }

  public function testMyTestCase()
  {
    $this->open("/svn/LOVD3/trunk/src/logout");
    $this->open("/svn/LOVD3/trunk/src/login");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/login$/',$this->getLocation()));
    $this->type("name=username", "collaborator");
    $this->type("name=password", "test1234");
    $this->click("//input[@value='Log in']");
    $this->waitForPageToLoad("30000");
    $this->open("/svn/LOVD3/trunk/src/");
    $this->click("id=tab_screenings");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/screenings$/',$this->getLocation()));
    $this->click("css=#0000000002 > td.ordered");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/screenings\/0000000002$/',$this->getLocation()));
    $this->click("id=viewentryOptionsButton_Screenings");
    $this->click("link=Add variant to screening");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/variants[\s\S]create&target=0000000002$/',$this->getLocation()));
    $this->click("//table[2]/tbody/tr[2]/td[2]/b");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/variants[\s\S]create&reference=Genome&target=0000000002$/',$this->getLocation()));
    $this->select("name=allele", "label=Maternal (confirmed)");
    $this->select("name=chromosome", "label=X");
    $this->type("name=VariantOnGenome/DNA", "g.40702876G>T");
    $this->click("link=PubMed");
    $this->type("name=VariantOnGenome/Reference", "{PMID:[2011]:[21520333]}");
    $this->type("name=VariantOnGenome/Frequency", "11/10000");
    $this->select("name=effect_reported", "label=Effect unknown");
    $this->click("//input[@value='Create variant entry']");
    for ($second = 0; ; $second++) {
        if ($second >= 60) $this->fail("timeout");
        try {
            if ($this->isElementPresent("css=table[class=info]")) break;
        } catch (Exception $e) {}
        sleep(1);
    }

    $this->assertTrue((bool)preg_match('/^Successfully processed your submission and sent an email notification to the relevant curator[\s\S]*$/',$this->getText("css=table[class=info]")));
    $this->waitForPageToLoad("4000");
  }
}
?>