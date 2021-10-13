<?php
/**
 * Created by jamieaitken on 30/07/2018 at 14:27
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Utils;


use Aws\Common\Aws;
use Monolog\Handler\ElasticSearchHandler;
use Monolog\Logger;

class LoggerService
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger('statusLogger');
    }

    public function push()
    {

    }
}