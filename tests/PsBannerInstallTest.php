<?php

class PsBannerInstallTest extends PsBannerTestCase
{
    public function testInstallSeedsEveryLanguageAndRegistersHooks()
    {
        $module = $this->newModule();
        $this->assertTrue($module->install());
        $this->assertSame('sale70.png', Configuration::get('BANNER_IMG', 1));
        $this->assertSame('sale70.png', Configuration::get('BANNER_IMG', 2));
        $this->assertSame('', Configuration::get('BANNER_LINK', 1));
        $this->assertSame('', Configuration::get('BANNER_DESC', 1));
        $hooks = Module::getHooksFor('ps_banner');
        $this->assertContains('displayHome', $hooks);
        $this->assertContains('actionObjectLanguageAddAfter', $hooks);
    }

    public function testInstallStopsWhenParentInstallFails()
    {
        Module::setParentInstallResult(false);
        $module = $this->newModule();
        $this->assertFalse($module->install());
        $this->assertNull(Configuration::get('BANNER_IMG', 1));
    }

    public function testInstallStopsWhenDisplayHomeHookFails()
    {
        Module::setRegisterHookResult('displayHome', false);
        $module = $this->newModule();
        $this->assertFalse($module->install());
        $this->assertNotContains('actionObjectLanguageAddAfter', Module::getHooksFor('ps_banner'));
    }
}
