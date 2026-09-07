<?php

return array(
    'host' => getenv('SANDWORTH_DB_HOST') ? getenv('SANDWORTH_DB_HOST') : 'localhost',
    'port' => getenv('SANDWORTH_DB_PORT') ? getenv('SANDWORTH_DB_PORT') : '3306',
    'database' => getenv('SANDWORTH_DB_NAME') ? getenv('SANDWORTH_DB_NAME') : 'sandwor2_sandworth_rentals',
    'username' => getenv('SANDWORTH_DB_USER') ? getenv('SANDWORTH_DB_USER') : 'sandwor2_erp',
    'password' => getenv('SANDWORTH_DB_PASS') ? getenv('SANDWORTH_DB_PASS') : 'mR65+TNKB_F3',
    'charset' => 'utf8mb4',
);
