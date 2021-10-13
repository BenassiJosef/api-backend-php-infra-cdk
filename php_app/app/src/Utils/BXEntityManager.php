<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 06/12/2016
 * Time: 20:18
 */

namespace App\Utils;

use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$paths     = ['app/src/Models/'];
$isDevMode = false;

$dbParams = [
    'driver'   => 'pdo_mysql',
    'platform' => new MySQL80Platform(),
];



$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
$em     = EntityManager::create($dbParams, $config);
