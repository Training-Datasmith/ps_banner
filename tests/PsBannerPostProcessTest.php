<?php

class PsBannerPostProcessTest extends PsBannerTestCase
{
    public function testSubmitWithoutFileUpdatesLinkAndDescriptionAndKeepsImage()
    {
        Configuration::updateValue('BANNER_IMG', [1 => 'sale70.png', 2 => 'sale70.png']);
        Configuration::updateValue('BANNER_LINK', [1 => '', 2 => '']);
        Configuration::updateValue('BANNER_DESC', [1 => 'old', 2 => 'old2']);
        Tools::setSubmit('submitStoreConf', true);
        Tools::setValue('BANNER_LINK_1', 'https://new-link.test');
        Tools::setValue('BANNER_DESC_1', 'new desc');
        Tools::setValue('BANNER_LINK_2', 'https://fr.test');
        Tools::setValue('BANNER_DESC_2', 'fr desc');

        $module = $this->newModule();
        WidgetModuleStub::setCacheEntry($module->getCacheId('ps_banner'), 'CACHE_SENTINEL');

        $result = $module->postProcess();
        $this->assertContainsString('The settings have been updated.', $result);
        $this->assertSame('https://new-link.test', Configuration::get('BANNER_LINK', 1));
        $this->assertSame('new desc', Configuration::get('BANNER_DESC', 1));
        $this->assertSame('sale70.png', Configuration::get('BANNER_IMG', 1));

        $rendered = $module->renderWidget('displayHome', []);
        $this->assertContainsString('new desc', $rendered);
        $this->assertNotContains('CACHE_SENTINEL', $rendered);
    }

    public function testOversizedUploadReturnsErrorAndDoesNotSave()
    {
        Configuration::updateValue('BANNER_LINK', [1 => 'https://seed.test']);
        Tools::setSubmit('submitStoreConf', true);
        $_FILES['BANNER_IMG_1'] = [
            'name' => 'big.jpg',
            'tmp_name' => '/tmp/not-used',
            'size' => 4000001,
        ];
        Tools::setValue('BANNER_LINK_1', 'https://changed.test');

        $module = $this->newModule();
        $result = $module->postProcess();
        $this->assertContainsString('File is too large', $result);
        $this->assertSame('https://seed.test', Configuration::get('BANNER_LINK', 1));
    }

    public function testDisallowedExtensionReturnsErrorAndWritesNoFile()
    {
        $imgDir = dirname(__DIR__) . '/img';
        $before = glob($imgDir . '/*');
        Tools::setSubmit('submitStoreConf', true);
        $_FILES['BANNER_IMG_1'] = [
            'name' => 'a.php',
            'tmp_name' => '/tmp/not-used',
            'size' => 100,
        ];
        $module = $this->newModule();
        $result = $module->postProcess();
        $this->assertContainsString('Invalid image format', $result);
        $this->assertSame($before, glob($imgDir . '/*'));
    }

    public function testFailedMoveReturnsUploadError()
    {
        Configuration::updateValue('BANNER_IMG', [1 => 'keep.png']);
        Tools::setSubmit('submitStoreConf', true);
        $_FILES['BANNER_IMG_1'] = [
            'name' => 'a.jpg',
            'tmp_name' => tempnam(sys_get_temp_dir(), 'psb'),
            'size' => 100,
        ];
        $module = $this->newModule();
        $result = $module->postProcess();
        $this->assertContainsString('An error occurred while attempting to upload the file.', $result);
        $this->assertSame('keep.png', Configuration::get('BANNER_IMG', 1));
    }

    public function testFirstLanguageValidationErrorDoesNotSaveLaterLanguages()
    {
        Configuration::updateValue('BANNER_LINK', [2 => 'https://fr-seed.test']);
        Tools::setSubmit('submitStoreConf', true);
        $_FILES['BANNER_IMG_1'] = [
            'name' => 'bad.php',
            'tmp_name' => '/tmp/x',
            'size' => 10,
        ];
        Tools::setValue('BANNER_LINK_2', 'https://fr-changed.test');
        $module = $this->newModule();
        $module->postProcess();
        $this->assertSame('https://fr-seed.test', Configuration::get('BANNER_LINK', 2));
    }

    public function testGetContentAfterSubmitContainsConfirmationAndForm()
    {
        Tools::setSubmit('submitStoreConf', true);
        Tools::setValue('BANNER_LINK_1', 'https://x.test');
        Tools::setValue('BANNER_DESC_1', 'x');
        Tools::setValue('BANNER_LINK_2', '');
        Tools::setValue('BANNER_DESC_2', '');
        $module = $this->newModule();
        $html = $module->getContent();
        $this->assertContainsString('The settings have been updated.', $html);
        $this->assertContainsString('BANNER_LINK', $html);
    }
}
