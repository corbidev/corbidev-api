<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$projectDir = dirname(__DIR__);
$defaultTestDatabasePath = str_replace('\\', '/', $projectDir.'/var/test_default.sqlite');
$authTestDatabasePath = str_replace('\\', '/', $projectDir.'/var/test_auth.sqlite');

if (!class_exists(Kernel::class, false)) {
    require dirname(__DIR__).'/src/Kernel.php';
}

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = 'false';

if (class_exists(Dotenv::class)) {
    (new Dotenv())->bootEnv($projectDir.'/.env');
}

$_SERVER['DATABASE_AUTH_URL'] = $_ENV['DATABASE_AUTH_URL'] = getenv('DATABASE_AUTH_URL') ?: 'sqlite:///'.$authTestDatabasePath;
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = getenv('DATABASE_URL') ?: 'sqlite:///'.$defaultTestDatabasePath;

putenv('DATABASE_AUTH_URL=' . $_SERVER['DATABASE_AUTH_URL']);
putenv('DATABASE_URL=' . $_SERVER['DATABASE_URL']);


