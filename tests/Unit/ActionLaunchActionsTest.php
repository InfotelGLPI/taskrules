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

use GlpiPlugin\Taskrules\Action;
use PHPUnit\Framework\TestCase;

/**
 * Stub PC qui capture chaque appel à update() sans toucher la base.
 */
class StubComputerForAction extends \CommonDBTM
{
    public static $notable = false;
    public static array $capturedInputs = [];

    public static function getTypeName($nb = 0): string { return 'Computer'; }
    public static function getTable($classname = null): string { return 'glpi_computers'; }

    public function getFromDB($ID): bool
    {
        $this->fields = ['id' => $ID];
        return true;
    }

    public function update($input, $history = 1, $options = []): bool
    {
        static::$capturedInputs[] = $input;
        return true;
    }
}

/**
 * Stub règle minimaliste : seule la propriété $fields est utilisée par Action::launchActions().
 */
class StubRuleForAction
{
    public array $fields;
    public function __construct(array $fields) { $this->fields = $fields; }
}

class ActionLaunchActionsTest extends TestCase
{
    protected function setUp(): void
    {
        global $DB;

        StubComputerForAction::$capturedInputs = [];

        // Remplace $DB par un bouchon qui retourne un résultat vide pour
        // toutes les requêtes (Ticket_User::find, Group_User::getUserGroups…).
        $DB = new class {
            public function request($criteria, ...$args): \ArrayIterator
            {
                return new \ArrayIterator([]);
            }
        };
    }

    protected function tearDown(): void
    {
        global $DB;
        $DB = null;
        StubComputerForAction::$capturedInputs = [];
    }

    /**
     * Scénario :
     *   - Ticket sans demandeur (→ users_id = 0, groups_id = 0)
     *   - PC lié avec statut "en stock"
     *   - Règle : statut → "en production" (states_id=2), lieu → "Stock" (locations_id=5),
     *             vider l'utilisateur affecté, vider le groupe affecté
     *
     * Attendu : les quatre champs sont mis à jour sur le PC.
     */
    public function testPcStatusLocationUserGroupUpdatedWhenTaskDone(): void
    {
        $rule = new StubRuleForAction([
            'state_action'    => 2,  // affecter un statut spécifique
            'states_id'       => 2,  // "en production"
            'location_action' => 3,  // affecter un lieu spécifique
            'locations_id'    => 5,  // "Stock"
            'contact_action'  => 0,  // ne pas modifier
            'user_action'     => 1,  // vider l'utilisateur affecté
            'group_action'    => 1,  // vider le groupe affecté
        ]);

        $item_linked = [
            'itemtype' => StubComputerForAction::class,
            'items_id' => 42,
        ];

        Action::launchActions($rule, 1, 10, $item_linked);

        $this->assertNotEmpty(
            StubComputerForAction::$capturedInputs,
            'update() aurait dû être appelé au moins une fois sur le PC lié.'
        );

        // $input s'accumule entre les blocs d'actions : fusionner tous les
        // appels pour obtenir l'état final de chaque champ.
        $merged = array_merge(...StubComputerForAction::$capturedInputs);

        $this->assertSame(2, $merged['states_id'],    'Le statut doit passer à "en production" (states_id=2).');
        $this->assertSame(5, $merged['locations_id'], 'Le lieu doit être défini à "Stock" (locations_id=5).');
        $this->assertSame(0, $merged['users_id'],     "L'utilisateur affecté doit être vidé (users_id=0).");
        $this->assertSame(0, $merged['groups_id'],    'Le groupe affecté doit être vidé (groups_id=0).');
    }
}