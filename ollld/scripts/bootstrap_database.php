<?php

require_once dirname(__DIR__) . '/app/Core/Database.php';
require_once dirname(__DIR__) . '/app/Core/Platform.php';

$config = require dirname(__DIR__) . '/config/database.php';
$database = new App\Core\Database($config, dirname(__DIR__) . '/database/schema.sql');
$platform = new App\Core\Platform($database, dirname(__DIR__) . '/storage');

$platform->initialize();

echo "Database ready.\n";
