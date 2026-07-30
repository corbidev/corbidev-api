<?php
require 'vendor/autoload.php';

$_SERVER['APP_ENV'] = 'test';
$_SERVER['APP_DEBUG'] = 'false';
$_SERVER['DATABASE_AUTH_URL'] = 'mysql://user:password@127.0.0.1:3366/symfony-api_auth?serverVersion=11.7.0-MariaDB&charset=utf8mb4';
$_SERVER['DATABASE_URL'] = 'mysql://user:password@127.0.0.1:3376/symfony-api?serverVersion=11.7.0-MariaDB&charset=utf8mb4';
putenv('DATABASE_AUTH_URL=' . $_SERVER['DATABASE_AUTH_URL']);
putenv('DATABASE_URL=' . $_SERVER['DATABASE_URL']);

$kernel = new App\Kernel('test', false);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager('auth');
$conn = $em->getConnection();

$client = new App\Auth\Entity\ApiClient('Client', 'client-test', Symfony\Component\Uid\Uuid::v4());
$em->persist($client);
$em->flush();

var_dump($conn->fetchOne("SELECT COUNT(*) FROM auth_api_client"));
var_dump($conn->fetchAllAssociative("SELECT id, client_id FROM auth_api_client"));

$kernel->shutdown();
