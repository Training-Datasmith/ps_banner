<?php

class PsBannerWidgetTest extends PsBannerTestCase
{
    /** @var string */
    private $moduleDir;

    protected function setUp()
    {
        parent::setUp();
        $this->moduleDir = sys_get_temp_dir() . '/ps_banner_widget_' . uniqid('', true);
        mkdir($this->moduleDir . '/img', 0777, true);
        if (!defined('PS_BANNER_WIDGET_MODULE_DIR')) {
            define('PS_BANNER_WIDGET_MODULE_DIR', $this->moduleDir);
        }
        // Re-point module dir for widget image resolution.
        $GLOBALS['ps_banner_test_module_dir'] = $this->moduleDir;
    }

    protected function tearDown()
    {
        if (is_dir($this->moduleDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->moduleDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($this->moduleDir);
        }
        parent::tearDown();
    }

    private function moduleWithWidgetDir()
    {
        if (!is_dir(_PS_MODULE_DIR_)) {
            mkdir(_PS_MODULE_DIR_ . 'ps_banner/img', 0777, true);
        }
        $targetDir = _PS_MODULE_DIR_ . 'ps_banner/img/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        return $this->newModule();
    }

    public function testWidgetUsesTheCurrentLanguage()
    {
        Configuration::updateValue('BANNER_LINK', [1 => 'https://en.test', 2 => 'https://fr.test']);
        Configuration::updateValue('BANNER_DESC', [1 => 'en', 2 => 'fr']);
        $module = $this->moduleWithWidgetDir();
        $module->context->language->id = 2;
        $vars = $module->getWidgetVariables('displayHome', []);
        $this->assertSame('fr', $vars['banner_desc']);
        $this->assertSame('https://fr.test', $vars['banner_link']);
    }

    public function testHttpLinkIsUnchangedAndBareHostGetsHttp()
    {
        $module = $this->moduleWithWidgetDir();
        Configuration::updateValue('BANNER_LINK', [1 => 'http://shop.example/a']);
        $vars = $module->getWidgetVariables('displayHome', []);
        $this->assertSame('http://shop.example/a', $vars['banner_link']);

        Configuration::updateValue('BANNER_LINK', [1 => 'shop.example/sale']);
        $vars = $module->getWidgetVariables('displayHome', []);
        $this->assertSame('http://shop.example/sale', $vars['banner_link']);
    }

    public function testEmptyLinkUsesHomepageWithoutAddingAScheme()
    {
        Configuration::updateValue('BANNER_LINK', [1 => '']);
        $module = $this->moduleWithWidgetDir();
        $vars = $module->getWidgetVariables('displayHome', []);
        $this->assertSame('https://shop.test/', $vars['banner_link']);
        $this->assertInternalType('string', $vars['banner_link']);
    }

    public function testExistingImageIsReturnedWithItsPixelSize()
    {
        $src = dirname(__DIR__) . '/img/sale70.png';
        $dest = _PS_MODULE_DIR_ . 'ps_banner/img/sale70.png';
        copy($src, $dest);
        Configuration::updateValue('BANNER_IMG', [1 => 'sale70.png']);
        Configuration::updateValue('BANNER_LINK', [1 => 'https://x.test']);
        Configuration::updateValue('BANNER_DESC', [1 => 'Summer']);

        $module = $this->moduleWithWidgetDir();
        $module->context->language->id = 1;
        $rendered = $module->renderWidget('displayHome', []);
        $this->assertContainsString('https://media.test/modules/ps_banner/img/sale70.png', $rendered);
        $this->assertContainsString('banner_width=1110', $rendered);
        $this->assertContainsString('banner_height=213', $rendered);

        $png = $dest . '.1x1.png';
        $this->createTinyPng($png);
        Configuration::updateValue('BANNER_IMG', [1 => 'sale70.png.1x1.png']);
        copy($png, _PS_MODULE_DIR_ . 'ps_banner/img/sale70.png.1x1.png');
        $rendered = $module->renderWidget('displayHome', []);
        $this->assertContainsString('banner_width=1', $rendered);
        $this->assertContainsString('banner_height=1', $rendered);
    }

    public function testMissingImageOmitsTheImageAndKeepsLinkAndDescription()
    {
        Configuration::updateValue('BANNER_IMG', [1 => 'missing.png']);
        Configuration::updateValue('BANNER_LINK', [1 => 'https://shop.test/promo']);
        Configuration::updateValue('BANNER_DESC', [1 => 'Summer sale']);
        $module = $this->moduleWithWidgetDir();
        $module->context->language->id = 1;
        $rendered = $module->renderWidget('displayHome', []);
        $this->assertContainsString('Summer sale', $rendered);
        $this->assertContainsString('banner_link=https://shop.test/promo', $rendered);
        $this->assertNotContains('img/missing.png', $rendered);
        $vars = $module->getWidgetVariables('displayHome', []);
        $this->assertNotEmpty($vars['banner_link']);
    }

    public function testRenderWidgetUsesCacheAndTemplate()
    {
        Configuration::updateValue('BANNER_LINK', [1 => 'https://fresh.test']);
        Configuration::updateValue('BANNER_DESC', [1 => 'fresh']);
        $module = $this->moduleWithWidgetDir();
        WidgetModuleStub::setCacheEntry($module->getCacheId('ps_banner'), 'CACHE_SENTINEL_91');
        $cached = $module->renderWidget('displayHome', []);
        $this->assertSame('CACHE_SENTINEL_91', $cached);
        $this->assertNotContains('fresh', $cached);

        WidgetModuleStub::clearCache();
        $live = $module->renderWidget('displayHome', []);
        $this->assertContainsString('module:ps_banner/ps_banner.tpl', $live);
        $this->assertContainsString('https://fresh.test', $live);
    }

    private function createTinyPng($path)
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        file_put_contents($path, $png);
    }
}
