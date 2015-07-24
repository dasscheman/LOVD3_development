<?php
/*******************************************************************************
 *
 * LEIDEN OPEN VARIATION DATABASE (LOVD)
 *
 * Created     : 2014-12-19
 * Modified    : 2015-07-21:12:41:07
 * For LOVD    : 3.0-12
 *
 * Copyright   : 2014 Leiden University Medical Center; http://www.LUMC.nl/
 * Programmers : Msc. Daan Asscheman <D.Asscheman@LUMC.nl>
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

class admin_tests extends PHPUnit_Extensions_SeleniumTestCase
{
    protected $captureScreenshotOnFailure = TRUE;
    protected $screenshotPath = '/home/dasscheman/svn/LOVD3_development/trunk/tests/test_results/error_screenshots';
    protected $screenshotUrl = 'trunk/tests/test_results/error_screenshots';

    protected function setUp()
    {
        $this->setHost('localhost');
        $this->setPort(4444);
        $this->setBrowser("firefox");
        $this->setBrowserUrl("http://localhost/svn/LOVD3_development/");
        $this->shareSession(true);
    }
    public function testInstallLOVD()
    {
        $this->open("/svn/LOVD3_development/trunksrc/install/");
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("30000");
        $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/install\/[\s\S]step=1$/',$this->getLocation()));
        $this->type("name=name", "LOVD3 Admin");
        $this->type("name=institute", "Leiden University Medical Center");
        $this->type("name=department", "Human Genetics");
        $this->type("name=address", "Einthovenweg 20\n2333 ZC Leiden");
        $this->type("name=email", "I.F.A.C.Fokkema@LUMC.nl");
        $this->type("name=telephone", "+31 (0)71 526 9438");
        $this->type("name=username", "admin");
        $this->type("name=password_1", "test1234");
        $this->type("name=password_2", "test1234");
        $this->select("name=countryid", "label=Netherlands");
        $this->type("name=city", "Leiden");
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("30000");
        $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/install\/[\s\S]step=1&sent=true$/',$this->getLocation()));
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("30000");
        $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/install\/[\s\S]step=2$/',$this->getLocation()));
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("30000");
        $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/install\/[\s\S]step=3$/',$this->getLocation()));
        $this->type("name=institute", "Leiden University Medical Center");
        $this->type("name=email_address", "noreply@LOVD.nl");
        $this->type("name=proxy_host", "localhost");
        $this->type("name=proxy_port", "3128");
        $this->type("name=proxy_username", "test");
        $this->type("name=proxy_password", "test");
        $this->click("name=send_stats");
        $this->click("name=include_in_listing");
        $this->uncheck("name=lock_uninstall");
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("30000");
        $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/install\/[\s\S]step=3&sent=true$/',$this->getLocation()));
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("30000");
        $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/install\/[\s\S]step=4$/',$this->getLocation()));
        $this->click("css=button");
        $this->waitForPageToLoad("30000");
        $this->assertTrue((bool)preg_match('/^[\s\S]*\/trunk\/src\/setup[\s\S]newly_installed$/',$this->getLocation()));
    }
    public function testCreateGeneIVD()
    {
        $this->open("/svn/LOVD3_development/trunksrc/logout");
        $this->open("/svn/LOVD3_development/trunksrc/login");
        $this->type("name=username", "admin");
        $this->type("name=password", "test1234");
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("30000");
        $this->open("/svn/LOVD3_development/trunksrc/genes?create");
        $this->type("name=hgnc_id", "IVD");
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("50000");
        $this->addSelection("name=active_transcripts[]", "label=transcript variant 1 (NM_002225.3)");
        $this->click("name=show_hgmd");
        $this->click("name=show_genecards");
        $this->click("name=show_genetests");
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("Successfully created the gene information entry!", $this->getText("css=table[class=info]"));
    }
    public function testUninstallLOVD()
    {
        $this->open("/svn/LOVD3_development/trunksrc/logout");
        $this->open("/svn/LOVD3_development/trunksrc/login");
        $this->type("name=username", "admin");
        $this->type("name=password", "test1234");
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("30000");
        $this->open("/svn/LOVD3_development/trunksrc/uninstall");
        $this->type("name=password", "test1234");
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("30000");
        $this->type("name=password", "test1234");
        $this->click("css=input[type=\"submit\"]");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("LOVD successfully uninstalled!\nThank you for having used LOVD!", $this->getText("css=div[id=lovd__progress_message]"));
    }
}
?>
