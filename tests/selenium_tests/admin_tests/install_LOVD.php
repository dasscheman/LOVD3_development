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
    $this->open("/svn/LOVD3/trunk/src/install/");
    $this->assertContains("/src/install/", $this->getLocation());
    $this->assertContains("install", $this->getBodyText());
    $this->isElementPresent("//input[@value='Start >>']");
    $this->click("//input[@value='Start >>']");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/install\/[\s\S]step=1$/',$this->getLocation()));
    $this->type("name=name", "LOVD3 Admin");
    $this->type("name=institute", "Leiden University Medical Center");
    $this->type("name=department", "Human Genetics");
    $this->type("name=address", "Einthovenweg 20\n2333 ZC Leiden");
    $this->type("name=email", "test@lovd.nl");
    $this->type("name=telephone", "+31 (0)71 526 9438");
    $this->type("name=username", "admin");
    $this->type("name=password_1", "test1234");
    $this->type("name=password_2", "test1234");
    $this->select("name=countryid", "label=Netherlands");
    $this->type("name=city", "Leiden");
    $this->click("//input[@value='Continue »']");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/install\/[\s\S]step=1&sent=true$/',$this->getLocation()));
    $this->click("//input[@value='Next >>']");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/install\/[\s\S]step=2$/',$this->getLocation()));
    $this->click("//input[@value='Next >>']");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/install\/[\s\S]step=3$/',$this->getLocation()));
    $this->type("name=institute", "Leiden University Medical Center");
    $this->type("name=email_address", "noreply@LOVD.nl");
    $this->select("name=refseq_build", "label=hg19 / GRCh37");
    $this->click("name=send_stats");
    $this->click("name=include_in_listing");
    $this->uncheck("name=lock_uninstall");
    $this->click("//input[@value='Continue »']");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/install\/[\s\S]step=3&sent=true$/',$this->getLocation()));
    $this->click("//input[@value='Next >>']");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/install\/[\s\S]step=4$/',$this->getLocation()));
    $this->click("css=button");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/src\/setup[\s\S]newly_installed$/',$this->getLocation()));
  }
}
?>