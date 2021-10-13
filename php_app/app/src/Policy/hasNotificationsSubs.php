<?php
/**
 * Created by jamieaitken on 27/11/2017 at 12:27
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Policy;

use App\Models\Integrations\ChargeBee\Subscriptions;
use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\ORM\EntityManager;

class hasNotificationsSubs
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function __invoke(Request $request, Response $response, $next)
    {
        $notificationSub = $this
            ->em
            ->getRepository(Subscriptions::class)
            ->findOneBy(
                [
                    'customer_id' => $request->getAttribute('user')['id'],
                    'plan_id'     => 'notifications',
                    'status'      => 'active'
                ]
            );

        if (is_null($notificationSub)) {
            return $response->withStatus(403);
        }

        return $next($request, $response);
    }
}