<?php

/**
 * -------------------------------------------------------------------------
 * Taskrules plugin for GLPI
 * Copyright (C) 2025-2026 by the Taskrules Development Team.
 *
 * https://github.com/InfotelGLPI/Taskrules
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Taskrules.
 *
 * Taskrules is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * Taskrules is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Taskrules. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Taskrules\Tests\Unit;

use GlpiPlugin\Taskrules\Condition;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ConditionDecodeFieldTest extends TestCase
{
    private function decode(mixed $value): array
    {
        $method = new ReflectionMethod(Condition::class, 'decodeField');
        $method->setAccessible(true);
        return $method->invoke(null, $value);
    }

    public function testValidJsonArrayReturnsDecodedArray(): void
    {
        $result = $this->decode(json_encode(['field' => 1, 'searchtype' => 'equals', 'value' => 'test']));

        $this->assertSame(['field' => 1, 'searchtype' => 'equals', 'value' => 'test'], $result);
    }

    public function testInvalidJsonReturnsEmptyArray(): void
    {
        $result = $this->decode('not-valid-json{{{');

        $this->assertSame([], $result);
    }

    public function testSerializedPhpArrayIsDecoded(): void
    {
        $data       = ['field' => 'serial', 'value' => 'test'];
        $serialized = serialize($data);

        $result = $this->decode($serialized);

        $this->assertSame($data, $result);
    }

    public function testSerializedPhpCriteriaFromDbIsDecoded(): void
    {
        // Real value stored in glpi_plugin_taskrules_conditions.fields_computer for taskrules_id=3
        $serialized = 'a:2:{i:0;a:4:{s:4:"link";s:3:"AND";s:5:"field";s:1:"4";s:10:"searchtype";s:6:"equals";s:5:"value";s:2:"18";}i:2;a:2:{s:4:"link";s:3:"AND";s:17:"criteria_computer";a:2:{i:0;a:4:{s:4:"link";s:3:"AND";s:5:"field";s:2:"31";s:10:"searchtype";s:6:"equals";s:5:"value";s:2:"16";}i:1;a:4:{s:4:"link";s:2:"OR";s:5:"field";s:2:"31";s:10:"searchtype";s:6:"equals";s:5:"value";s:2:"17";}}}}';

        $result = $this->decode($serialized);

        $this->assertCount(2, $result);
        $this->assertSame('AND', $result[0]['link']);
        $this->assertSame('4', $result[0]['field']);
        $this->assertSame('equals', $result[0]['searchtype']);
        $this->assertSame('18', $result[0]['value']);
        $this->assertArrayHasKey('criteria_computer', $result[2]);
        $this->assertCount(2, $result[2]['criteria_computer']);
    }

    public function testInvalidStringReturnsEmptyArray(): void
    {
        $result = $this->decode('not-valid-json{{{and-not-serialized-either');

        $this->assertSame([], $result);
    }

    public function testEmptyStringReturnsEmptyArray(): void
    {
        $result = $this->decode('');

        $this->assertSame([], $result);
    }

    public function testNullReturnsEmptyArray(): void
    {
        $result = $this->decode(null);

        $this->assertSame([], $result);
    }

    public function testJsonObjectReturnsEmptyArray(): void
    {
        $result = $this->decode(json_encode(['key' => 'value']));

        $this->assertIsArray($result);
    }
}
