<?php

class PsBannerLegacyMigrationTest extends PsBannerTestCase
{
    public function testMigrationDoesNothingWhenOldModuleIsAbsent()
    {
        Configuration::updateValue('BANNER_IMG', [1 => 'keep.png']);
        $module = $this->newModule();
        $this->assertTrue($module->uninstallPrestaShop16Module());
        $this->assertSame('keep.png', Configuration::get('BANNER_IMG', 1));
    }

    public function testMigrationCopiesMultilingualValuesAndUninstallsOldModule()
    {
        Module::setInstalled('blockbanner', true);
        Configuration::updateValue('BLOCKBANNER_IMG', [1 => 'old1.png', 2 => 'old2.png']);
        Configuration::updateValue('BLOCKBANNER_LINK', [1 => 'https://a.test', 2 => 'https://b.test']);
        Configuration::updateValue('BLOCKBANNER_DESC', [1 => 'd1', 2 => 'd2']);

        $module = $this->newModule();
        $this->assertTrue($module->uninstallPrestaShop16Module());

        $this->assertSame('old1.png', Configuration::get('BANNER_IMG', 1));
        $this->assertSame('old2.png', Configuration::get('BANNER_IMG', 2));
        $this->assertSame('https://a.test', Configuration::get('BANNER_LINK', 1));
        $this->assertSame('d2', Configuration::get('BANNER_DESC', 2));
        $this->assertFalse(Module::isInstalled('blockbanner'));
    }
}
