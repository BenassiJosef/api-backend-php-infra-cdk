<?php

namespace App\Controllers\Integrations\Mikrotik;

use App\Controllers\Integrations\Mail\_MailController;
use App\Utils\CacheEngine;
use Doctrine\ORM\EntityManager;

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 14/12/2016
 * Time: 13:50
 */
class _MikrotikController
{

    /**
     * @var EntityManager
     */

    protected $em;

    /**
     * @var _MailController
     */

    protected $mail;

    /**
     * _MikrotikController constructor.
     * @param EntityManager $em
     */

    public function __construct(EntityManager $em)
    {
        $this->em   = $em;
        $this->mail = new _MailController($this->em);
    }
}
