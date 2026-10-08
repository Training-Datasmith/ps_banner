<?php

class PsBannerUploadTest extends PsBannerTestCase
{
    /** @var resource|null */
    private static $serverProc;

    /** @var int */
    private static $serverPort;

    /** @var string */
    private static $moduleCopy;

    /** @var string */
    private static $sale70Hash;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        try {
            self::$sale70Hash = hash_file('sha256', dirname(__DIR__) . '/img/sale70.png');
            self::$moduleCopy = sys_get_temp_dir() . '/ps_banner_upload_' . uniqid('', true);
            mkdir(self::$moduleCopy . '/img', 0777, true);
            copy(dirname(__DIR__) . '/ps_banner.php', self::$moduleCopy . '/ps_banner.php');
            copy(dirname(__DIR__) . '/img/sale70.png', self::$moduleCopy . '/img/sale70.png');

            self::$serverPort = self::pickEphemeralPort();
            $cmd = sprintf(
                '%s -S 127.0.0.1:%d -t %s',
                escapeshellarg(PHP_BINARY),
                self::$serverPort,
                escapeshellarg(__DIR__ . '/Support')
            );
            putenv('PS_BANNER_MODULE_COPY=' . self::$moduleCopy);
            $descriptor = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];
            self::$serverProc = proc_open($cmd, $descriptor, $pipes);
            if (!is_resource(self::$serverProc)) {
                throw new RuntimeException('Failed to start upload server');
            }
            fclose($pipes[0]);
            self::waitForServerReady($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
        } catch (Exception $e) {
            self::stopUploadServer();
            if (is_string(self::$moduleCopy) && is_dir(self::$moduleCopy)) {
                self::removeDir(self::$moduleCopy);
                self::$moduleCopy = '';
            }
            throw $e;
        }
    }

    public static function tearDownAfterClass()
    {
        self::stopUploadServer();
        if (is_dir(self::$moduleCopy)) {
            self::removeDir(self::$moduleCopy);
        }
        $hash = hash_file('sha256', dirname(__DIR__) . '/img/sale70.png');
        if ($hash !== self::$sale70Hash) {
            throw new RuntimeException('repo img/sale70.png was modified');
        }
        parent::tearDownAfterClass();
    }

    public function testUploadForOneLanguageKeepsImageStillUsedByAnotherLanguage()
    {
        $response = $this->multipartUpload([
            'submitStoreConf' => '1',
            'banner_img_1' => 'sale70.png',
            'banner_img_2' => 'sale70.png',
            'has_context_1' => '1',
            'has_context_2' => '1',
            'BANNER_LINK_1' => 'https://en-new.test',
            'BANNER_LINK_2' => '',
            'BANNER_DESC_1' => '',
            'BANNER_DESC_2' => '',
        ], 'fresh-banner.png', self::jpegBody());

        $this->assertTrue($response['sale70_exists'], 'sale70.png must remain when another language still references it');
        $this->assertSame('sale70.png', $response['img2']);
        $expected = md5('fresh-banner.png') . '.png';
        $this->assertSame($expected, $response['img1']);
        $this->assertFileExists(self::$moduleCopy . '/img/' . $expected);
        $this->assertContainsString('The settings have been updated.', $response['result']);
    }

    public function testUploadDeletesImageNoOtherLanguageUses()
    {
        file_put_contents(self::$moduleCopy . '/img/only-en.jpg', self::jpegBody());
        file_put_contents(self::$moduleCopy . '/img/only-fr.jpg', self::jpegBody());

        $response = $this->multipartUpload([
            'submitStoreConf' => '1',
            'banner_img_only_en' => 'only-en.jpg',
            'banner_img_only_fr' => 'only-fr.jpg',
            'BANNER_LINK_1' => '',
            'BANNER_LINK_2' => '',
            'BANNER_DESC_1' => '',
            'BANNER_DESC_2' => '',
        ], 'new-en.jpg', self::jpegBody());

        $this->assertFalse($response['only_en_exists']);
        $this->assertTrue($response['only_fr_exists']);
        $expected = md5('new-en.jpg') . '.jpg';
        $this->assertSame($expected, $response['img1']);
        $this->assertFileExists(self::$moduleCopy . '/img/' . $expected);
    }

    public function testUploadDoesNotDeleteWhenImageHasNoShopContext()
    {
        file_put_contents(self::$moduleCopy . '/img/only-en.jpg', self::jpegBody());
        // Server resets config; pass via POST without has_context flag.
        $response = $this->multipartUpload([
            'submitStoreConf' => '1',
            'banner_img_1' => 'only-en.jpg',
            'banner_img_2' => 'sale70.png',
            'BANNER_LINK_1' => '',
            'BANNER_LINK_2' => '',
            'BANNER_DESC_1' => '',
            'BANNER_DESC_2' => '',
        ], 'replacement.jpg', self::jpegBody());

        $this->assertTrue(file_exists(self::$moduleCopy . '/img/only-en.jpg'));
        $expected = md5('replacement.jpg') . '.jpg';
        $this->assertSame($expected, $response['img1']);
        $this->assertFileExists(self::$moduleCopy . '/img/' . $expected);
    }

    private function multipartUpload(array $fields, $filename, $body)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'psbup');
        file_put_contents($tmpFile, $body);

        $url = 'http://127.0.0.1:' . self::$serverPort . '/upload-endpoint.php';
        $cmd = ['curl', '-sS', $url];
        foreach ($fields as $key => $value) {
            $cmd[] = '-F';
            $cmd[] = $key . '=' . $value;
        }
        $cmd[] = '-F';
        $cmd[] = 'BANNER_IMG_1=@' . $tmpFile . ';filename=' . $filename . ';type=image/jpeg';

        $escaped = array_map('escapeshellarg', $cmd);
        $raw = shell_exec(implode(' ', $escaped));
        unlink($tmpFile);

        $this->assertNotNull($raw, 'upload HTTP request failed');
        $decoded = json_decode($raw, true);
        $this->assertInternalType('array', $decoded);

        return $decoded;
    }

    private static function jpegBody()
    {
        return file_get_contents(dirname(__DIR__) . '/img/sale70.png');
    }

    private static function stopUploadServer()
    {
        if (is_resource(self::$serverProc)) {
            proc_terminate(self::$serverProc);
            proc_close(self::$serverProc);
            self::$serverProc = null;
        }
    }

    private static function pickEphemeralPort()
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($socket === false) {
            throw new RuntimeException($errstr);
        }
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        $parts = explode(':', $address);

        return (int) end($parts);
    }

    private static function waitForServerReady($stderrPipe)
    {
        stream_set_blocking($stderrPipe, false);
        $deadline = microtime(true) + 10.0;
        while (microtime(true) < $deadline) {
            $health = @file_get_contents('http://127.0.0.1:' . self::$serverPort . '/health.php');
            if ($health !== false && strpos($health, 'ok') !== false) {
                return;
            }
            usleep(50000);
        }
        throw new RuntimeException('upload server did not become ready');
    }

    private static function removeDir($dir)
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}
