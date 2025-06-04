<?php

use Symfony\Component\Dotenv\Dotenv;
use App\Kernel;
use Doctrine\ORM\Tools\SchemaTool;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) ($_SERVER['APP_DEBUG'] ?? false));
$kernel->boot();
$entityManager = $kernel->getContainer()->get('doctrine')->getManager();
$metadata = $entityManager->getMetadataFactory()->getAllMetadata();
$schemaTool = new SchemaTool($entityManager);
$schemaTool->dropDatabase();
if (!empty($metadata)) {
    $schemaTool->createSchema($metadata);
}
$kernel->shutdown();
