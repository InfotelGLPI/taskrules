<?php

namespace GlpiPlugin\Taskrules\Tests\Unit;

use GlpiPlugin\Taskrules\Log;
use PHPUnit\Framework\TestCase;

class StubItemForLog extends \CommonDBTM
{
    public static $notable = false;

    public static string $stubSerial = '';

    public static function getTypeName($nb = 0): string
    {
        return 'StubItem';
    }

    public function getFromDB($ID): bool
    {
        $this->fields = ['serial' => static::$stubSerial, 'id' => $ID];
        return true;
    }

    public function getLink($options = []): string
    {
        return '<a href="#">stub-link</a>';
    }

    public static function getTable($classname = null): string
    {
        return 'glpi_computers';
    }
}

class LogShowResultsTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION['glpiID'] = 0;
        $_SESSION['glpi_currenttime'] = date('Y-m-d H:i:s');
        StubItemForLog::$stubSerial = '';
    }

    protected function tearDown(): void
    {
        unset($_SESSION['plugin_taskrules']);
    }

    public function testSerialXssIsHtmlEncodedInNotFoundSection(): void
    {
        $xss = '<script>alert(1)</script>';
        StubItemForLog::$stubSerial = $xss;

        $results = [
            'errors' => [
                'not_found' => [
                    ['itemtype' => StubItemForLog::class, 'items_id' => 1],
                ],
            ],
        ];

        ob_start();
        Log::showResults(null, $results, 0, 0);
        $output = ob_get_clean();

        $this->assertStringNotContainsString($xss, $output);
        $this->assertStringContainsString(htmlspecialchars($xss, ENT_QUOTES, 'UTF-8'), $output);
    }

    public function testCleanSerialIsOutputInNotFoundSection(): void
    {
        StubItemForLog::$stubSerial = 'SN-12345';

        $results = [
            'errors' => [
                'not_found' => [
                    ['itemtype' => StubItemForLog::class, 'items_id' => 2],
                ],
            ],
        ];

        ob_start();
        Log::showResults(null, $results, 0, 0);
        $output = ob_get_clean();

        $this->assertStringContainsString('SN-12345', $output);
    }

    public function testEmptySerialRendersWithoutError(): void
    {
        StubItemForLog::$stubSerial = '';

        $results = [
            'errors' => [
                'not_found' => [
                    ['itemtype' => StubItemForLog::class, 'items_id' => 3],
                ],
            ],
        ];

        ob_start();
        Log::showResults(null, $results, 0, 0);
        $output = ob_get_clean();

        $this->assertIsString($output);
    }
}
