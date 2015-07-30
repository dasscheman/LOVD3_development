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
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/submit\/screening\/0000000001$/',$this->getLocation()));
    $this->click("//tr[3]/td[2]/b");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/submit\/finish\/individual\/00000001$/',$this->getLocation()));
    $this->waitForPageToLoad("4000");
    $this->open("svn/LOVD3/trunk/src/individuals/00000001");
    $this->assertEquals("Public", $this->getText("//tr[8]/td"));
  }
}
?>