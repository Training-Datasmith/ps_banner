<?php

class PsBannerLanguageHookTest extends PsBannerTestCase
{
    public function testNewLanguageReceivesDefaultLanguageImage()
    {
        Language::resetLanguages([
            1 => ['id_lang' => 1, 'iso_code' => 'en', 'name' => 'English'],
            2 => ['id_lang' => 2, 'iso_code' => 'fr', 'name' => 'French'],
            3 => ['id_lang' => 3, 'iso_code' => 'de', 'name' => 'German'],
        ]);
        Configuration::setGlobal('PS_LANG_DEFAULT', 1);
        Configuration::updateValue('BANNER_IMG', [1 => 'en.png', 2 => 'fr.png']);
        Configuration::updateValue('BANNER_LINK', [1 => 'https://en.test', 2 => 'https://fr.test']);
        Configuration::updateValue('BANNER_DESC', [1 => 'en desc', 2 => 'fr desc']);

        $module = $this->newModule();
        $module->hookActionObjectLanguageAddAfter(['object' => (object) ['id' => 3]]);

        $this->assertSame('en.png', Configuration::get('BANNER_IMG', 3));
        $this->assertSame('', Configuration::get('BANNER_LINK', 3));
        $this->assertSame('', Configuration::get('BANNER_DESC', 3));
        $this->assertSame('fr.png', Configuration::get('BANNER_IMG', 2));
        $this->assertSame('https://fr.test', Configuration::get('BANNER_LINK', 2));
    }
}
