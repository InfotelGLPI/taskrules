<?php

$loader = require dirname(__DIR__, 3) . '/vendor/autoload.php';

if (!defined('GLPI_PLUGINS_DIRECTORIES')) {
    define('GLPI_PLUGINS_DIRECTORIES', []);
}

$loader->addPsr4('GlpiPlugin\\Taskrules\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('GlpiPlugin\\Taskrules\\Tests\\Unit\\', dirname(__DIR__) . '/tests/Unit/');

class_exists(\Computer::class);
