<?php

namespace GlpiPlugin\Taskrules\Tests\Unit;

use GlpiPlugin\Taskrules\Rule;
use PHPUnit\Framework\TestCase;

class RulePrepareInputTest extends TestCase
{
    private function makeRule(): Rule
    {
        $rule = new Rule();
        $rule->fields = [];
        return $rule;
    }

    public function testPrepareInputForAddFiltersUnknownFields(): void
    {
        $rule = $this->makeRule();

        $result = $rule->prepareInputForAdd([
            'active'           => 1,
            'malicious_field'  => 'injected',
            'tasktemplates_id' => 5,
        ]);

        $this->assertArrayNotHasKey('malicious_field', $result);
    }

    public function testPrepareInputForAddKeepsWhitelistedFields(): void
    {
        $rule = $this->makeRule();

        $result = $rule->prepareInputForAdd([
            'active'            => 1,
            'preventcreation'   => 0,
            'preventchange'     => 0,
            'trigger_creation'  => 1,
            'trigger_update'    => 0,
            'tasktemplates_id'  => 3,
        ]);

        $this->assertArrayHasKey('active', $result);
        $this->assertArrayHasKey('trigger_creation', $result);
        $this->assertArrayHasKey('tasktemplates_id', $result);
    }

    public function testPrepareInputForAddEncodesUsedItemtypes(): void
    {
        $rule = $this->makeRule();

        $result = $rule->prepareInputForAdd([
            'used_itemtypes' => ['Computer', 'Monitor'],
        ]);

        $this->assertSame('["Computer","Monitor"]', $result['used_itemtypes']);
    }

    public function testPrepareInputForAddWithEmptyUsedItemtypesEncodesEmptyArray(): void
    {
        $rule = $this->makeRule();

        $result = $rule->prepareInputForAdd([
            'active' => 1,
        ]);

        $this->assertSame('[]', $result['used_itemtypes']);
    }

    public function testPrepareInputForUpdateFiltersUnknownFields(): void
    {
        $rule = $this->makeRule();

        $result = $rule->prepareInputForUpdate([
            'id'              => 1,
            'active'          => 1,
            'evil_field'      => 'payload',
        ]);

        $this->assertArrayNotHasKey('evil_field', $result);
    }

    public function testPrepareInputForUpdateKeepsId(): void
    {
        $rule = $this->makeRule();

        $result = $rule->prepareInputForUpdate([
            'id'     => 7,
            'active' => 0,
        ]);

        $this->assertArrayHasKey('id', $result);
        $this->assertSame(7, $result['id']);
    }

    public function testPrepareInputForUpdateEncodesUsedItemtypes(): void
    {
        $rule = $this->makeRule();

        $result = $rule->prepareInputForUpdate([
            'id'             => 2,
            'used_itemtypes' => ['Printer'],
        ]);

        $this->assertSame('["Printer"]', $result['used_itemtypes']);
    }
}
