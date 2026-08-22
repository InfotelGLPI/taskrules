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

$loader = require dirname(__DIR__, 3) . '/vendor/autoload.php';

if (!defined('GLPI_PLUGINS_DIRECTORIES')) {
    define('GLPI_PLUGINS_DIRECTORIES', []);
}

$loader->addPsr4('GlpiPlugin\\Taskrules\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('GlpiPlugin\\Taskrules\\Tests\\Unit\\', dirname(__DIR__) . '/tests/Unit/');

class_exists(\Computer::class);
