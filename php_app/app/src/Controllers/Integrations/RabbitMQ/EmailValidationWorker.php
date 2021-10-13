<?php
/**
 * Created by jamieaitken on 14/06/2018 at 16:54
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\RabbitMQ;


use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Registrations\_ValidationController;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class EmailValidationWorker
{
    protected $mail;
    protected $em;

    public function __construct(_MailController $mail, EntityManager $em)
    {
        $this->mail = $mail;
        $this->em   = $em;
    }

    public function runWorkerRoute(Request $request, Response $response)
    {
        $this->runWorker($request->getParsedBody());
        $this->em->clear();
    }

    public function runWorker(array $body)
    {
        $newValidation  = new _ValidationController($this->em);
        $mp             = new _Mixpanel();
        $sendValidation = $newValidation->sendValidate($body['serial'], $body['id']);
        if ($sendValidation['status'] === 200) {
            $mp->track('NEW_VALIDATION_SEND', $sendValidation);
        } else {
            $mp->track('VALIDATION_SEND_ERROR', $sendValidation);
        }
    }

}