<?php

/*
 -------------------------------------------------------------------------
 Taskrules plugin for GLPI
 Copyright (C) 2025-2026 by the Taskrules Development Team.

 https://github.com/InfotelGLPI/Taskrules
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Taskrules.

 Taskrules is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 Taskrules is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Taskrules. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

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
