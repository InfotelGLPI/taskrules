<?php

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

    public function testSerializedPhpStringReturnsEmptyArray(): void
    {
        $serialized = serialize(['field' => 'serial', 'value' => '<script>alert(1)</script>']);

        $result = $this->decode($serialized);

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
