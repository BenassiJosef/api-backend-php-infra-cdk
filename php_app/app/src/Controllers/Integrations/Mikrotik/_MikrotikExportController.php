<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 23/03/2017
 * Time: 12:05
 */

namespace App\Controllers\Integrations\Mikrotik;

use Doctrine\ORM\EntityManager;

class _MikrotikExportController extends _MikrotikConfigController
{

    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    public function export($to = '', $serial = '')
    {
        $ip      = gethostbyname('email-smtp.eu-west-1.amazonaws.com');
        $command = '/export file=export' . PHP_EOL;
        $command .= '/tool e-mail' . PHP_EOL;
        $command .= 'set address=' . $ip . ' from=mikrotik@stampede.ai password=' . getenv('mail_password') . ' port=587 start-tls=tls-only user=' . getenv('mail_username') . PHP_EOL;
        $command .= '/tool e-mail send to="' . $to . '" subject="$[/system identity get name] export" body="$[/system clock get date] configuration file" file=export.rsc';

        return $this->buildConfig($command, $serial);
    }

    public function genericCommand($command = '', $serial = '')
    {
        return $this->buildConfig($command, $serial);
    }
}
