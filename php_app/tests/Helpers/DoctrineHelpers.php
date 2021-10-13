<?php

namespace StampedeTests\Helpers;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\DBAL\Types\Type;
use DoctrineExtensions\Query\Mysql\CharLength;
use DoctrineExtensions\Query\Mysql\Date;
use DoctrineExtensions\Query\Mysql\DateFormat;
use DoctrineExtensions\Query\Mysql\DateSub;
use DoctrineExtensions\Query\Mysql\Day;
use DoctrineExtensions\Query\Mysql\FromUnixtime;
use DoctrineExtensions\Query\Mysql\GroupConcat;
use DoctrineExtensions\Query\Mysql\Hour;
use DoctrineExtensions\Query\Mysql\IfNull;
use DoctrineExtensions\Query\Mysql\MatchAgainst;
use DoctrineExtensions\Query\Mysql\Minute;
use DoctrineExtensions\Query\Mysql\Month;
use DoctrineExtensions\Query\Mysql\Now;
use DoctrineExtensions\Query\Mysql\Round;
use DoctrineExtensions\Query\Mysql\StrToDate;
use DoctrineExtensions\Query\Mysql\TimestampDiff;
use DoctrineExtensions\Query\Mysql\TimeToSec;
use DoctrineExtensions\Query\Mysql\UnixTimestamp;
use DoctrineExtensions\Query\Mysql\Week;
use DoctrineExtensions\Query\Mysql\Year;
use DoctrineExtensions\Query\Mysql\IfElse;
use Ramsey\Uuid\Doctrine\UuidType;

class DoctrineHelpers
{
    public static function createEntityManager()
    {
        $paths = [dirname(__DIR__) . '../app/src/Models'];

        $isDevMode = true;

        // the TEST DB connection configuration
        $connectionParams = [
            'host'     => '127.0.0.1',
            'port'     => getenv('MYSQL_PORT') ? getenv('MYSQL_PORT') : 3307,
            'driver'   => 'pdo_mysql',
            'user'     => 'blackbx74',
            'password' => 'hunter2',
            'dbname'   => 'core',
        ];

        $config = Setup::createConfiguration($isDevMode);

        if (!Type::hasType('uuid')) {
            Type::addType('uuid', UuidType::class);
        }

        $config->addCustomDatetimeFunction('TIMESTAMPDIFF', TimestampDiff::class);
        $config->addCustomDatetimeFunction('NOW', Now::class);
        $config->addCustomDatetimeFunction('MINUTE', Minute::class);
        $config->addCustomDatetimeFunction('DATESUB', DateSub::class);
        $config->addCustomDatetimeFunction('YEAR', Year::class);
        $config->addCustomDatetimeFunction('MONTH', Month::class);
        $config->addCustomDatetimeFunction('DAY', Day::class);
        $config->addCustomDatetimeFunction('HOUR', Hour::class);
        $config->addCustomDatetimeFunction('WEEK', Week::class);
        $config->addCustomDatetimeFunction('UNIX_TIMESTAMP', UnixTimestamp::class);
        $config->addCustomDatetimeFunction('DATE', Date::class);
        $config->addCustomDatetimeFunction('DATE_FORMAT', DateFormat::class);
        $config->addCustomDatetimeFunction('TIME_TO_SEC', TimeToSec::class);
        $config->addCustomDatetimeFunction('FROM_UNIXTIME', FromUnixtime::class);
        $config->addCustomDatetimeFunction('ROUND', Round::class);
        $config->addCustomNumericFunction('CHAR_LENGTH', CharLength::class);
        $config->addCustomStringFunction('GROUP_CONCAT', GroupConcat::class);
        $config->addCustomStringFunction('STR_TO_DATE', StrToDate::class);
        $config->addCustomStringFunction('MATCH_AGAINST', MatchAgainst::class);
        $config->addCustomStringFunction('IF', IfElse::class);
        $config->addCustomStringFunction('IFNULL', IfNull::class);
        
        $driver = new AnnotationDriver(new AnnotationReader(), $paths);

        AnnotationRegistry::registerLoader('class_exists');
        $config->setMetadataDriverImpl($driver);

        return EntityManager::create($connectionParams, $config);
    }
}