<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';
require_once 'configs/database.php';

use Illuminate\Database\Capsule\Manager as DB;

// Set up Environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
$capsule = new DB();

$databaseConnections = databaseConfigs() ?? ['connections' => []];

foreach ($databaseConnections['connections'] as $connectionName => $config) {
    $capsule->addConnection($config, $connectionName);
}

$capsule->setAsGlobal();
$capsule->bootEloquent();
