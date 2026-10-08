<?php

class PsBannerConstructorTest extends PsBannerTestCase
{
    public function testModuleIdentity()
    {
        $module = $this->newModule();
        $this->assertSame('ps_banner', $module->name);
        $this->assertSame('front_office_features', $module->tab);
        $this->assertSame('2.1.2', $module->version);
        $this->assertSame('PrestaShop', $module->author);
        $this->assertSame(0, $module->need_instance);
        $this->assertTrue($module->bootstrap);
        $this->assertSame('8.1.0', $module->ps_versions_compliancy['min']);
        $this->assertSame('Banner', $module->displayName);
        $this->assertContainsString('Add a banner to the homepage', $module->description);
    }

    public function testConfigXmlMatchesClass()
    {
        $module = $this->newModule();
        $xml = simplexml_load_file(dirname(__DIR__) . '/config.xml');
        $this->assertSame((string) $xml->name, $module->name);
        $this->assertSame((string) $xml->version, $module->version);
    }

    public function testTemplatePlaceholders()
    {
        $tpl = file_get_contents(dirname(__DIR__) . '/ps_banner.tpl');
        $this->assertContainsString('href="{$banner_link}"', $tpl);
        $this->assertContainsString('src="{$banner_img}"', $tpl);
        $this->assertContainsString('alt="{$banner_desc}"', $tpl);
        $this->assertContainsString('width="{$banner_width}"', $tpl);
        $this->assertContainsString('height="{$banner_height}"', $tpl);
        $this->assertRegExp('/<img[^>]+>/', $tpl);
    }
}
