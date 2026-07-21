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

    /**
     * Render results.html.twig through a standalone Twig environment.
     *
     * The lightweight "Unit" suite does not bootstrap the GLPI kernel, so
     * GLPI's TemplateRenderer (which needs GLPI_CACHE_DIR) is unavailable.
     * We render the real template file with the same 'html' auto-escaping
     * strategy GLPI uses, so the escaping contract of {{ row.serial }} is
     * still exercised against the actual template.
     */
    private function renderResults(array $model): string
    {
        $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__, 2) . '/templates');
        $twig = new \Twig\Environment($loader, [
            'autoescape' => 'html',
            'cache'      => false,
        ]);
        // Stub GLPI's translation function: identity is enough for rendering.
        $twig->addFunction(new \Twig\TwigFunction('__', static fn($s) => $s));
        $twig->addFunction(new \Twig\TwigFunction('_n', static fn($s, $p, $n) => $n > 1 ? $p : $s));

        return $twig->render('results.html.twig', $model + [
            'not_found_rows' => [],
            'no_right_rows'  => [],
            'updated_blocks' => [],
        ]);
    }

    public function testSerialXssIsHtmlEncodedInNotFoundSection(): void
    {
        $xss = '<script>alert(1)</script>';
        StubItemForLog::$stubSerial = $xss;

        $rows = Log::buildNotFoundRows([
            ['itemtype' => StubItemForLog::class, 'items_id' => 1],
        ]);

        // The model carries the raw serial; escaping is the template's job.
        $this->assertSame($xss, $rows[0]['serial']);

        $output = $this->renderResults(['not_found_rows' => $rows]);

        $this->assertStringNotContainsString($xss, $output);
        $this->assertStringContainsString(htmlspecialchars($xss, ENT_QUOTES, 'UTF-8'), $output);
    }

    public function testCleanSerialIsOutputInNotFoundSection(): void
    {
        StubItemForLog::$stubSerial = 'SN-12345';

        $rows = Log::buildNotFoundRows([
            ['itemtype' => StubItemForLog::class, 'items_id' => 2],
        ]);

        $output = $this->renderResults(['not_found_rows' => $rows]);

        $this->assertStringContainsString('SN-12345', $output);
    }

    public function testEmptySerialRendersWithoutError(): void
    {
        StubItemForLog::$stubSerial = '';

        $rows = Log::buildNotFoundRows([
            ['itemtype' => StubItemForLog::class, 'items_id' => 3],
        ]);

        $output = $this->renderResults(['not_found_rows' => $rows]);

        $this->assertIsString($output);
    }
}
