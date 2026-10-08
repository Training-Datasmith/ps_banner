<?php

class PsBannerConfigFormTest extends PsBannerTestCase
{
    public function testGetConfigFieldsValuesUsesStoredConfig()
    {
        Configuration::updateValue('BANNER_IMG', [1 => 'a.png', 2 => 'b.png']);
        Configuration::updateValue('BANNER_LINK', [1 => 'https://one.test', 2 => 'https://two.test']);
        Configuration::updateValue('BANNER_DESC', [1 => 'one', 2 => 'two']);
        $module = $this->newModule();
        $fields = $module->getConfigFieldsValues();
        $this->assertSame('https://one.test', $fields['BANNER_LINK'][1]);
        $this->assertSame('https://two.test', $fields['BANNER_LINK'][2]);
    }

    public function testGetConfigFieldsValuesPrefersPostedValue()
    {
        Configuration::updateValue('BANNER_LINK', [1 => 'https://stored.test', 2 => 'https://two.test']);
        Tools::setValue('BANNER_LINK_1', 'https://posted.example/a');
        $module = $this->newModule();
        $fields = $module->getConfigFieldsValues();
        $this->assertSame('https://posted.example/a', $fields['BANNER_LINK'][1]);
        $this->assertSame('https://two.test', $fields['BANNER_LINK'][2]);
    }

    public function testRenderFormDeclaresTheThreeBannerFields()
    {
        $module = $this->newModule();
        $json = $module->renderForm();
        $this->assertContainsString('"name":"BANNER_IMG"', $json);
        $this->assertContainsString('"type":"file_lang"', $json);
        $this->assertContainsString('"name":"BANNER_LINK"', $json);
        $this->assertContainsString('"name":"BANNER_DESC"', $json);
        $this->assertContainsString('"submitStoreConf"', $json);
        $this->assertContainsString('configure=ps_banner', $json);
        $this->assertContainsString('tab_module=front_office_features', $json);
        $this->assertContainsString('module_name=ps_banner', $json);
        $this->assertContainsString('test-admin-token-AdminModules', $json);
    }

    public function testGetContentWithoutSubmitIsTheFormOnly()
    {
        $module = $this->newModule();
        $html = $module->getContent();
        $this->assertContainsString('BANNER_DESC', $html);
        $this->assertNotContains('The settings have been updated.', $html);
    }
}
