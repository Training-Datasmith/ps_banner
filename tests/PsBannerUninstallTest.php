<?php

class PsBannerUninstallTest extends PsBannerTestCase
{
    public function testUninstallRemovesBannerKeys()
    {
        Configuration::updateValue('BANNER_IMG', [1 => 'a.png']);
        Configuration::updateValue('BANNER_LINK', [1 => 'https://example.test']);
        Configuration::updateValue('BANNER_DESC', [1 => 'desc']);
        $module = $this->newModule();
        $this->assertTrue($module->uninstall());
        $this->assertNull(Configuration::get('BANNER_IMG', 1));
        $this->assertNull(Configuration::get('BANNER_LINK', 1));
        $this->assertNull(Configuration::get('BANNER_DESC', 1));
    }
}
