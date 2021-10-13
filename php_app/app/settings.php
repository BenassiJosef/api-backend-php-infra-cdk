<?php

return [
    'settings' => [
        // Slim Settings
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails'               => getenv('DISPLAY_ERRORS') === "true",
        'addContentLengthHeader'            => false,
        'PoweredBy'                         => 'Captive Ltd',
        'template'                          => '/src/Templates/',
        'chargebee'                         => [
            'enabled'              => getenv('CHARGEBEE_ENABLED') === "false" ? false : true
        ],
        'cancellationEmails' => getenv("CANCELLATION_EMAILS") === "" ? "feedback@stampede.ai" :  getenv("CANCELLATION_EMAILS"),
        'mail'                              => [
            'username' => getenv('mail_username'),
            'password' => getenv('mail_password'),
            'host'     => getenv('mail_host'),
            'port'     => getenv('mail_port'),
            'auth'     => true,
            'path'     => '/src/Templates/Emails'
        ],
        // database settings
        'pdo'                               => [
            'dsn'      => getenv('pdo_dsn'),
            'username' => getenv('pdo_username'),
            'password' => getenv('pdo_password')
        ],
        'doctrine'                          => [
            'meta'       => [
                'entity_path'           => [
                    'app/src/Models'
                ],
                'auto_generate_proxies' => getenv('disable_proxy_generation') ? false : true,
                'proxy_dir'             => __DIR__ . (getenv('proxy_dir') ? getenv('proxy_dir') : '/../cache/proxies'),
                'cache'                 => null,
            ],
            'connection' => [
                'wrapperClass'  => 'Doctrine\DBAL\Connections\MasterSlaveConnection',
                'driver'        => 'pdo_mysql',
                'master'        => [
                    'host'     => getenv('doctrine_connection_host'),
                    'dbname'   => getenv('doctrine_connection_dbname'),
                    'user'     => getenv('doctrine_connection_user'),
                    'password' => getenv('doctrine_connection_password')
                ],
                'slaves'        => [
                    [
                        'host'     => getenv('doctrine_connection_replica_host'),
                        'dbname'   => getenv('doctrine_connection_dbname'),
                        'user'     => getenv('doctrine_connection_user'),
                        'password' => getenv('doctrine_connection_password')
                    ]
                ],
                'charset'       => 'utf8mb4',
                'driverOptions' => [
                    'x_reconnect_attempts' => 10
                ]
            ],
            'radius'     => [
                'driver'        => 'pdo_mysql',
                'host'          => getenv('doctrine_radius_host'),
                'dbname'        => getenv('doctrine_radius_dbname'),
                'user'          => getenv('doctrine_radius_user'),
                'password'      => getenv('doctrine_radius_password'),
                'charset'       => 'utf8mb4',
                'driverOptions' => [
                    'x_reconnect_attempts' => 10
                ]
            ]
        ],
        // api rate limiter settings
        'api_rate_limiter'                  => [
            'requests' => '2000',
            'inmins'   => '60',
        ],
        'xero'                              => [
            'consumer_key'  => getenv('xero_consumer_key'),
            'shared_secret' => getenv('xero_secret_key')
        ],
        'aws'                               => [
            'bucket' => [
                'access_key' => getenv('aws_access_key'),
                'secret_key' => getenv('aws_secret_key'),
                'name'       => 'blackbx'
            ]
        ],
        // monolog settings
        'logger'                            => [
            'name'     => 'app',
            'path'     => __DIR__ . '/../log/app.log'
        ],
    ],
];
