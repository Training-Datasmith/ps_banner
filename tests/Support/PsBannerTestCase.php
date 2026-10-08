<?php

use PHPUnit\Framework\TestCase;

abstract class PsBannerTestCase extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        ps_banner_reset_test_state();
    }

    protected function newModule()
    {
        return new Ps_Banner();
    }

    protected function assertContainsString($needle, $haystack)
    {
        $this->assertNotFalse(strpos($haystack, $needle), 'Expected substring: ' . $needle);
    }
}
