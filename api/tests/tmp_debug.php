<?php
require 'vendor/autoload.php';

$kernel = new App\Kernel('test', false);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager('auth');
$em->beginTransaction();

$client = new App\Auth\Entity\ApiClient('Client', 'client', Symfony\Component\Uid\Uuid::v4());
$em->persist($client);
$em->flush();

$secret = new App\Auth\Entity\ApiClientSecret($client, 'hash', Symfony\Component\Uid\Uuid::v4());
$em->persist($secret);
$em->flush();

$repo = $em->getRepository(App\Auth\Entity\ApiClientSecret::class);
$res = $repo->findValidByApiClient($client);
var_dump(count($res));
foreach ($res as $item) {
    var_dump($item->getId()->toRfc4122());
}

$em->getConnection()->rollBack();
$kernel->shutdown();
