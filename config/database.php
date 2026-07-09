<?php

return [
    'driver' => 'json',
    'note' => 'Version 0.1 uses JSON storage so the portal can start without database setup. Replace repositories with MySQL later.',
    'mysql' => [
        'host' => '127.0.0.1',
        'database' => 'hit_portal',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
