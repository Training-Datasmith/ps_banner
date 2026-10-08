<?php

namespace PrestaShop\PrestaShop\Core\Module {
    interface WidgetInterface
    {
        public function renderWidget($hookName, array $configuration);

        public function getWidgetVariables($hookName, array $configuration);
    }
}

namespace {
    if (!defined('_PS_VERSION_')) {
        define('_PS_VERSION_', '8.1.0');
    }
    if (!defined('_PS_MODULE_DIR_')) {
        define('_PS_MODULE_DIR_', sys_get_temp_dir() . '/ps_modules/');
    }

class Context
{
    const DEVICE_MOBILE = 1;

    /** @var Context */
    public static $instance;

    /** @var Link */
    public $link;

    /** @var stdClass */
    public $language;

    /** @var AdminControllerStub */
    public $controller;

    public function __construct()
    {
        $this->link = new Link();
        $this->language = (object) ['id' => 1];
        $this->controller = new AdminControllerStub();
    }

    public static function getContext()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}

class AdminControllerStub
{
    public function getLanguages()
    {
        return Language::getLanguages(false);
    }
}

class Link
{
    public $protocol_content = 'https://';

    public function getPageLink($page)
    {
        return 'https://shop.test/';
    }

    public function getAdminLink($controller, $withToken = false)
    {
        return 'https://shop.test/admin/' . $controller;
    }
}

class Shop
{
    const CONTEXT_SHOP = 1;
    const CONTEXT_GROUP = 2;
    const CONTEXT_ALL = 3;

    public static function getContext()
    {
        return self::CONTEXT_SHOP;
    }
}

class Language
{
    public $id;

    /** @var array<int, array{id_lang:int, iso_code:string, name:string}> */
    private static $languages = [
        1 => ['id_lang' => 1, 'iso_code' => 'en', 'name' => 'English'],
        2 => ['id_lang' => 2, 'iso_code' => 'fr', 'name' => 'French'],
    ];

    public function __construct($id)
    {
        $this->id = (int) $id;
    }

    public static function resetLanguages(array $languages)
    {
        self::$languages = $languages;
    }

    public static function getLanguages($active = true)
    {
        return array_values(self::$languages);
    }

    public static function getIDs($active = true)
    {
        return array_keys(self::$languages);
    }
}

class Configuration
{
    /** @var array<string, array<int, mixed>> */
    private static $values = [];

    /** @var array<string, mixed> */
    private static $globals = [];

    /** @var array<string, array<int, bool>> */
    private static $contextFlags = [];

    public static function reset()
    {
        self::$values = [];
        self::$globals = [];
        self::$contextFlags = [];
    }

    public static function setGlobal($key, $value)
    {
        self::$globals[$key] = $value;
    }

    public static function get($key, $idLang = null, $idShopGroup = null, $idShop = null)
    {
        if ($idLang === null) {
            return array_key_exists($key, self::$globals) ? self::$globals[$key] : null;
        }
        $idLang = (int) $idLang;
        if (!isset(self::$values[$key][$idLang])) {
            return null;
        }

        return self::$values[$key][$idLang];
    }

    public static function updateValue($key, $value, $html = false, $idShopGroup = null, $idShop = null)
    {
        if (is_array($value)) {
            foreach ($value as $langId => $langValue) {
                self::$values[$key][(int) $langId] = $langValue;
            }

            return true;
        }
        self::$globals[$key] = $value;

        return true;
    }

    public static function deleteByName($key)
    {
        unset(self::$values[$key], self::$globals[$key], self::$contextFlags[$key]);
    }

    public static function hasContext($key, $idLang, $context)
    {
        return !empty(self::$contextFlags[$key][(int) $idLang]);
    }

    public static function setHasContext($key, $idLang, $has = true)
    {
        self::$contextFlags[$key][(int) $idLang] = $has;
    }

    public static function getConfigInMultipleLangs($key, $idShopGroup = null, $idShop = null)
    {
        if (defined('PS_BANNER_PS17_HARNESS') && PS_BANNER_PS17_HARNESS) {
            throw new RuntimeException('Configuration::getConfigInMultipleLangs must not be called on PS 1.7 harness');
        }
        $out = [];
        foreach (Language::getIDs() as $idLang) {
            $out[$idLang] = self::get($key, $idLang);
        }

        return $out;
    }

    public static function getInt($key, $idShopGroup = null, $idShop = null)
    {
        if (!defined('PS_BANNER_PS17_HARNESS') || !PS_BANNER_PS17_HARNESS) {
            throw new RuntimeException('Configuration::getInt must not be called on PS 8.1+ harness');
        }
        $out = [];
        foreach (Language::getIDs() as $idLang) {
            $out[$idLang] = self::get($key, $idLang);
        }

        return $out;
    }

}

class Tools
{
    /** @var array<string, mixed> */
    private static $values = [];

    /** @var array<string, bool> */
    private static $submits = [];

    private static $mediaServer = 'media.test';

    public static function reset()
    {
        self::$values = [];
        self::$submits = [];
        self::$mediaServer = 'media.test';
    }

    public static function setMediaServerHost($host)
    {
        self::$mediaServer = $host;
    }

    public static function isSubmit($key)
    {
        return !empty(self::$submits[$key]);
    }

    public static function setSubmit($key, $active = true)
    {
        self::$submits[$key] = $active;
    }

    public static function getValue($key, $default = null)
    {
        if (array_key_exists($key, self::$values)) {
            return self::$values[$key];
        }

        return $default;
    }

    public static function setValue($key, $value)
    {
        self::$values[$key] = $value;
    }

    public static function getMediaServer($filename)
    {
        return self::$mediaServer;
    }

    public static function getAdminTokenLite($tab)
    {
        return 'test-admin-token-' . $tab;
    }
}

class ImageManager
{
    public static function validateUpload(array $file, $maxFileSize)
    {
        if (isset($file['size']) && (int) $file['size'] > (int) $maxFileSize) {
            return 'File is too large';
        }
        $name = isset($file['name']) ? $file['name'] : '';
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed = ['gif', 'jpg', 'jpeg', 'jpe', 'png', 'webp'];
        if ($ext === '' || !in_array($ext, $allowed, true)) {
            return 'Invalid image format';
        }

        return false;
    }
}

class HelperForm
{
    public $show_toolbar;
    public $table;
    public $default_form_language;
    public $module;
    public $allow_employee_form_lang;
    public $identifier;
    public $submit_action;
    public $currentIndex;
    public $token;
    public $tpl_vars = [];

    public function generateForm(array $fields)
    {
        return json_encode(
            [
                'fields' => $fields,
                'submit_action' => $this->submit_action,
                'currentIndex' => $this->currentIndex,
                'token' => $this->token,
            ],
            JSON_UNESCAPED_SLASHES
        );
    }
}

class Module
{
    public $name;
    public $tab;
    public $version;
    public $author;
    public $need_instance;
    public $bootstrap;
    public $displayName;
    public $description;
    public $ps_versions_compliancy;
    public $table = 'module';
    public $identifier = 'id_module';

    /** @var Context */
    public $context;

    /** @var SmartyStub */
    public $smarty;

    public $_path = '/modules/ps_banner/';

    /** @var array<string, bool> */
    private static $installed = [];

    /** @var array<string, array<string>> */
    private static $hooks = [];

    /** @var array<string, Module> */
    private static $instances = [];

    /** @var bool */
    private static $parentInstallResult = true;

    /** @var array<string, bool> */
    private static $registerHookResults = [];

    /** @var bool */
    private static $disableDeviceCalled = false;

    public function __construct()
    {
        $this->context = Context::getContext();
        $this->smarty = new SmartyStub();
    }

    public static function resetAll()
    {
        self::$installed = [];
        self::$hooks = [];
        self::$instances = [];
        self::$parentInstallResult = true;
        self::$registerHookResults = [];
        self::$disableDeviceCalled = false;
        WidgetModuleStub::resetCache();
    }

    public static function setParentInstallResult($result)
    {
        self::$parentInstallResult = (bool) $result;
    }

    public static function setRegisterHookResult($hook, $result)
    {
        self::$registerHookResults[$hook] = (bool) $result;
    }

    public static function wasDisableDeviceCalled()
    {
        return self::$disableDeviceCalled;
    }

    public function install()
    {
        return self::$parentInstallResult;
    }

    public function uninstall()
    {
        return true;
    }

    public function registerHook($hook)
    {
        if (isset(self::$registerHookResults[$hook]) && !self::$registerHookResults[$hook]) {
            return false;
        }
        if (!isset(self::$hooks[$this->name])) {
            self::$hooks[$this->name] = [];
        }
        self::$hooks[$this->name][] = $hook;
        self::$installed[$this->name] = true;

        return true;
    }

    public static function getHooksFor($moduleName)
    {
        return isset(self::$hooks[$moduleName]) ? self::$hooks[$moduleName] : [];
    }

    public static function isInstalled($moduleName)
    {
        return !empty(self::$installed[$moduleName]);
    }

    public static function setInstalled($moduleName, $installed = true)
    {
        if ($installed) {
            self::$installed[$moduleName] = true;
        } else {
            unset(self::$installed[$moduleName]);
        }
    }

    public static function getInstanceByName($moduleName)
    {
        if (!isset(self::$instances[$moduleName])) {
            self::$instances[$moduleName] = new LegacyModuleStub($moduleName);
        }

        return self::$instances[$moduleName];
    }

    public static function setLegacyInstance($moduleName, LegacyModuleStub $instance)
    {
        self::$instances[$moduleName] = $instance;
    }

    public function disableDevice($device)
    {
        self::$disableDeviceCalled = true;

        return true;
    }

    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        return $id;
    }

    public function displayError($message)
    {
        return '[error]' . $message . '[/error]';
    }

    public function displayConfirmation($message)
    {
        return '[ok]' . $message . '[/ok]';
    }

    public function getPathUri()
    {
        return $this->_path;
    }

    public function isCached($template, $cacheId)
    {
        return WidgetModuleStub::isCached($cacheId);
    }

    public function getCacheId($suffix)
    {
        return 'ps_banner|' . $suffix;
    }

    public function fetch($template, $cacheId = null)
    {
        if ($cacheId !== null && WidgetModuleStub::isCached($cacheId)) {
            return WidgetModuleStub::getCached($cacheId);
        }

        return WidgetModuleStub::fetch($template, $this->smarty);
    }

    protected function _clearCache($template)
    {
        WidgetModuleStub::clearCache();
    }
}

class LegacyModuleStub extends Module
{
    private $legacyName;

    public $uninstallCalled = false;

    public function __construct($name)
    {
        parent::__construct();
        $this->legacyName = $name;
        $this->name = $name;
    }

    public function uninstall()
    {
        $this->uninstallCalled = true;
        Module::setInstalled($this->legacyName, false);

        return true;
    }
}

class SmartyStub
{
    /** @var array<string, mixed> */
    private $vars = [];

    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->vars[$k] = $v;
            }

            return;
        }
        $this->vars[$key] = $value;
    }

    public function getVars()
    {
        return $this->vars;
    }

    public function reset()
    {
        $this->vars = [];
    }
}

class WidgetModuleStub
{
    /** @var array<string, string> */
    private static $cache = [];

    public static function resetCache()
    {
        self::$cache = [];
    }

    public static function setCacheEntry($cacheId, $html)
    {
        self::$cache[$cacheId] = $html;
    }

    public static function getCached($cacheId)
    {
        return self::$cache[$cacheId];
    }

    public static function isCached($cacheId)
    {
        return array_key_exists($cacheId, self::$cache);
    }

    public static function clearCache()
    {
        self::$cache = [];
    }

    public static function fetch($template, SmartyStub $smarty)
    {
        $parts = [$template];
        foreach ($smarty->getVars() as $key => $value) {
            if (is_scalar($value)) {
                $parts[] = $key . '=' . $value;
            }
        }

        return implode('|', $parts);
    }
}

function ps_banner_reset_test_state()
{
    Configuration::reset();
    Tools::reset();
    Module::resetAll();
    Context::$instance = null;
    $_GET = [];
    $_POST = [];
    $_FILES = [];
    Configuration::setGlobal('PS_LANG_DEFAULT', 1);
    Configuration::setGlobal('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
    Language::resetLanguages([
        1 => ['id_lang' => 1, 'iso_code' => 'en', 'name' => 'English'],
        2 => ['id_lang' => 2, 'iso_code' => 'fr', 'name' => 'French'],
    ]);
}

} // namespace
