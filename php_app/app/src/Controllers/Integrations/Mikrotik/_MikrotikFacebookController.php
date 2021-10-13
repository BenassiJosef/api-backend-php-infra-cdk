<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 11/05/2017
 * Time: 16:47
 */

namespace App\Controllers\Integrations\Mikrotik;

use App\Controllers\Integrations\WalledGardenWhitelist;
use Doctrine\ORM\EntityManager;

class _MikrotikFacebookController extends _MikrotikConfigController
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em);
    }

    public function setFacebook($facebook, $serial)
    {
        if ($facebook['enabled']) {
            $whiteListController = new WalledGardenWhitelist();
            $command             = '';
            foreach ($whiteListController->getFacebookList() as $key => $domain) {
                $command .= '/ip hotspot walled-garden add comment="FACEBOOK" dst-host=*' . $domain . '*' . PHP_EOL;
            }
        } else {
            $command = '/ip hotspot walled-garden remove [find comment="FACEBOOK"]';
        }

        $this->buildConfig($command, $serial);
    }
}
