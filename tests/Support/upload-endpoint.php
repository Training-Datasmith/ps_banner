<?php

$initialPost = $_POST;
$initialFiles = $_FILES;

$moduleCopy = getenv('PS_BANNER_MODULE_COPY');
if ($moduleCopy === false || $moduleCopy === '') {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo "missing PS_BANNER_MODULE_COPY\n";
    exit(1);
}

if (!class_exists('Ps_Banner', false)) {
    require_once __DIR__ . '/PrestaShopHarness.php';
    require_once $moduleCopy . '/ps_banner.php';
}

ps_banner_reset_test_state();
$_POST = $initialPost;
$_FILES = $initialFiles;

foreach ($_POST as $key => $value) {
    Tools::setValue($key, $value);
}
Tools::setSubmit('submitStoreConf', true);

$languages = isset($_POST['languages']) ? (int) $_POST['languages'] : 2;
if ($languages === 2) {
    Language::resetLanguages([
        1 => ['id_lang' => 1, 'iso_code' => 'en', 'name' => 'English'],
        2 => ['id_lang' => 2, 'iso_code' => 'fr', 'name' => 'French'],
    ]);
}

if (!empty($_POST['banner_img_1'])) {
    Configuration::updateValue('BANNER_IMG', [1 => $_POST['banner_img_1'], 2 => $_POST['banner_img_2']]);
}
if (!empty($_POST['has_context_1'])) {
    Configuration::setHasContext('BANNER_IMG', 1, true);
}
if (!empty($_POST['has_context_2'])) {
    Configuration::setHasContext('BANNER_IMG', 2, true);
}
if (isset($_POST['banner_img_only_en'])) {
    Configuration::updateValue('BANNER_IMG', [
        1 => $_POST['banner_img_only_en'],
        2 => $_POST['banner_img_only_fr'],
    ]);
    Configuration::setHasContext('BANNER_IMG', 1, true);
    Configuration::setHasContext('BANNER_IMG', 2, true);
}

$module = new Ps_Banner();
$result = $module->postProcess();
$imgDirFiles = glob($moduleCopy . '/img/*');

header('Content-Type: application/json');
echo json_encode([
    'result' => $result,
    'img1' => Configuration::get('BANNER_IMG', 1),
    'img2' => Configuration::get('BANNER_IMG', 2),
    'sale70_exists' => file_exists($moduleCopy . '/img/sale70.png'),
    'only_en_exists' => file_exists($moduleCopy . '/img/only-en.jpg'),
    'only_fr_exists' => file_exists($moduleCopy . '/img/only-fr.jpg'),
    'img_dir_files' => array_map('basename', $imgDirFiles ? $imgDirFiles : []),
]);
