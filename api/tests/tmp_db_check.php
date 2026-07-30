<?php
require 'vendor/autoload.php';

$kernel = new App\Kernel('test', false);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager('auth');
$conn = $em->getConnection();

var_dump($conn->getDatabase());
var_dump($conn->fetchOne("SHOW TABLES LIKE 'auth_api_client'"));
var_dump($conn->fetchOne("SHOW TABLES LIKE 'auth_api_client_secret'"));
var_dump($conn->fetchOne("SHOW TABLES LIKE 'auth_api_client_scope'"));
var_dump($conn->fetchOne("SHOW TABLES LIKE 'auth_api_request'"));

$kernel->shutdown();
