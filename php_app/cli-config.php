<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 05/12/2016
 * Time: 18:33
 */

require __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\ORM\Tools\Console\ConsoleRunner;

// replace with mechanism to retrieve EntityManager in your app


$entityPath = array(__DIR__ . "/app/src/Models");

$config = Doctrine\ORM\Tools\Setup::createAnnotationMetadataConfiguration($entityPath, true);
$config->setMetadataDriverImpl(
    new Doctrine\ORM\Mapping\Driver\AnnotationDriver(
        new Doctrine\Common\Annotations\CachedReader(
            new Doctrine\Common\Annotations\AnnotationReader(),
            new Doctrine\Common\Cache\ArrayCache()
        ),
        $entityPath
    )
);

$connectionOptions = array(
    'driver'   => 'pdo_mysql',
    'platform' => new MySQL80Platform(),
);

$em = \Doctrine\ORM\EntityManager::create($connectionOptions, $config);


$helpers = new Symfony\Component\Console\Helper\HelperSet(
    [
        'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
        'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
    ]
);
