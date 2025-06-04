<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Use an in-memory SQLite database for tests to avoid requiring PostgreSQL
$_SERVER['DATABASE_URL'] = 'sqlite:///:memory:';
$_ENV['DATABASE_URL'] = 'sqlite:///:memory:';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
