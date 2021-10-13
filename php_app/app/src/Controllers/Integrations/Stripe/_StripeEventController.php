<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 14/02/2017
 * Time: 16:28
 */

namespace App\Controllers\Integrations\Stripe;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Stripe\Event;

class _StripeEventController extends _StripeController
{

    public function __construct(Logger $logger, EntityManager $em)
    {
        parent::__construct($logger, $em);
    }

    public function getEvent(string $id = '')
    {
        parent::init(null);

        $stripeEvent = function ($id) {
            return Event::retrieve($id);
        };

        return parent::handleErrors($stripeEvent, $id);
    }
}
