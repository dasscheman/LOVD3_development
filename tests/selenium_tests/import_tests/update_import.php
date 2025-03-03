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
    $this->open("/svn/LOVD3/trunk/src/import");
    $this->type("name=import", "/www/svn/LOVD3/trunk/tests/test_data_files/UpdateImport.txt");
    $this->select("name=mode", "label=Update existing data (in beta)");
    $this->click("//input[@value='Import file']");
    $this->waitForPageToLoad("30000");
    $this->assertTrue((bool)preg_match('/^[\s\S]*The following sections are modified and updated in the database: Columns, Diseases, Individuals, Phenotypes, Screenings, Variants_On_Genome, Variants_On_Transcripts\.$/',$this->getText("id=lovd_sql_progress_message_done")));
  }
}
?>