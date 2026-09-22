<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only.';
    exit;
}

require_once __DIR__ . '/app/Core/Router.php';
require_once __DIR__ . '/app/Core/View.php';
require_once __DIR__ . '/app/Core/Flash.php';
require_once __DIR__ . '/app/Core/Database.php';
require_once __DIR__ . '/app/Core/Platform.php';
require_once __DIR__ . '/app/Core/Auth.php';
require_once __DIR__ . '/app/Core/Mailer.php';
require_once __DIR__ . '/app/Support/helpers.php';

$dbConfig = require __DIR__ . '/config/database.php';
$database = new App\Core\Database($dbConfig, __DIR__ . '/database/schema.sql');
$platform = new App\Core\Platform($database, __DIR__ . '/storage');
$platform->initialize();

$result = $platform->flushQueuedTenantRegistrationEmails();

echo 'SMTP enabled: ' . ($result['smtpEnabled'] ? 'yes' : 'no') . PHP_EOL;
echo 'Sent: ' . $result['sent'] . PHP_EOL;
echo 'Failed: ' . $result['failed'] . PHP_EOL;
echo 'Skipped: ' . $result['skipped'] . PHP_EOL;
echo 'Remaining in queue: ' . $result['remaining'] . PHP_EOL;