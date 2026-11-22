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

namespace GlpiPlugin\Taskrules\Tests\Integration;

use Computer;
use Glpi\Tests\DbTestCase;
use GlpiPlugin\Taskrules\Condition;
use GlpiPlugin\Taskrules\Rule;
use Item_Ticket;
use Planning;
use TaskTemplate;
use Ticket;
use TicketTask;

/**
 * With "Prevent task creation on error" / "Prevent the task from changing its status in case
 * of an error", an item the actions of the rule may not update is an error, as an item that
 * does not match the criteria is: the task is not created, or does not become done.
 */
class RuleUpdateRightTest extends DbTestCase
{
    private int $tickets_id;

    private int $tasktemplates_id;

    public function setUp(): void
    {
        parent::setUp();

        $this->login('glpi', 'glpi');

        $computer = $this->createItem(Computer::class, [
            'name'        => 'TASKRULES-PC',
            'entities_id' => 0,
        ]);
        $this->tickets_id = $this->createItem(Ticket::class, [
            'name'        => 'Ticket with a computer',
            'content'     => 'Ticket with a computer',
            'entities_id' => 0,
        ])->getID();
        $this->createItem(Item_Ticket::class, [
            'tickets_id' => $this->tickets_id,
            'itemtype'   => Computer::class,
            'items_id'   => $computer->getID(),
        ]);
        $this->tasktemplates_id = $this->createItem(TaskTemplate::class, [
            'name'        => 'Template with a rule',
            'content'     => 'Template with a rule',
            'entities_id' => 0,
        ])->getID();
    }

    /**
     * A rule emptying the status of the computers named TASKRULES-PC
     */
    private function createTaskRule(array $options): void
    {
        $rule = $this->createItem(Rule::class, $options + [
            'tasktemplates_id' => $this->tasktemplates_id,
            'used_itemtypes'   => [Computer::class],
            'active'           => 1,
            'preventcreation'  => 1,
            'preventchange'    => 1,
            'trigger_creation' => 1,
            'trigger_update'   => 1,
            'state_action'     => 1,
        ], ['used_itemtypes']);

        $this->createItem(Condition::class, [
            'taskrules_id'    => $rule->getID(),
            'fields_computer' => json_encode([
                ['link' => 'AND', 'field' => '1', 'searchtype' => 'contains', 'value' => 'TASKRULES-PC'],
            ]),
        ]);
    }

    private function withoutComputerUpdateRight(): void
    {
        $_SESSION['glpiactiveprofile']['computer'] = READ;
    }

    private function preAdd(): TicketTask
    {
        $task = new TicketTask();
        $task->input = [
            'tickets_id'       => $this->tickets_id,
            'tasktemplates_id' => $this->tasktemplates_id,
            'content'          => 'Task of the template',
        ];
        Rule::preAddTask($task);

        return $task;
    }

    private function preUpdateToDone(): TicketTask
    {
        $task = $this->createItem(TicketTask::class, [
            'tickets_id'       => $this->tickets_id,
            'tasktemplates_id' => $this->tasktemplates_id,
            'content'          => 'Task of the template',
            'state'            => Planning::TODO,
        ]);
        $task->input = ['id' => $task->getID(), 'state' => Planning::DONE];
        Rule::preUpdateTask($task);

        return $task;
    }

    public function testCreationRefusedWithoutUpdateRight(): void
    {
        $this->createTaskRule([]);
        $this->withoutComputerUpdateRight();

        $this->assertFalse($this->preAdd()->input);
        $this->hasSessionMessages(ERROR, [
            'You are not allowed to update Computer TASKRULES-PC, task not created',
        ]);
    }

    public function testCreationAllowedWithUpdateRight(): void
    {
        $this->createTaskRule([]);

        $this->assertIsArray($this->preAdd()->input);
    }

    public function testCreationNotPreventedWhenTheOptionIsOff(): void
    {
        $this->createTaskRule(['preventcreation' => 0]);
        $this->withoutComputerUpdateRight();

        $this->assertIsArray($this->preAdd()->input);
    }

    public function testCreationNotPreventedWhenNoActionRunsOnCreation(): void
    {
        $this->createTaskRule(['trigger_creation' => 0]);
        $this->withoutComputerUpdateRight();

        $this->assertIsArray($this->preAdd()->input);
    }

    public function testDoneRefusedWithoutUpdateRight(): void
    {
        $this->createTaskRule([]);
        $this->withoutComputerUpdateRight();

        $this->assertArrayNotHasKey('state', $this->preUpdateToDone()->input);
        $this->hasSessionMessages(ERROR, [
            'You are not allowed to update Computer TASKRULES-PC, task not updated',
        ]);
    }

    public function testDoneAllowedWithUpdateRight(): void
    {
        $this->createTaskRule([]);

        $this->assertSame(Planning::DONE, $this->preUpdateToDone()->input['state']);
    }

    public function testDoneNotPreventedWhenTheOptionIsOff(): void
    {
        $this->createTaskRule(['preventchange' => 0]);
        $this->withoutComputerUpdateRight();

        $this->assertSame(Planning::DONE, $this->preUpdateToDone()->input['state']);
    }
}
