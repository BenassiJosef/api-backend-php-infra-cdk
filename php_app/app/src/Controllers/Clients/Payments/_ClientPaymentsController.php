<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 20/01/2017
 * Time: 17:33
 */

namespace App\Controllers\Clients\Payments;

use App\Utils\Http;
use Doctrine\ORM\EntityManager;

class _ClientPaymentsController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getTransactions(int $profileId = 0, string $serial)
    {

        $query = 'SELECT up.id, 
                  SUM(up.duration) as duration,
                  SUM(up.payment_amount) as cost, 
                  SUM(up.devices) as devices,
                  COUNT(up.id) as  payments,
                  up.profileId
             FROM 
            user_profile u
            JOIN user_payments up ON u.id = up.profileId
            WHERE u.id = :id
            AND up.creationdate + INTERVAL duration HOUR > NOW()
            AND serial = :serial';

        $builder = $this->em->getConnection();
        $stmt    = $builder->prepare($query);

        $stmt->execute(
            [
                'id'     => $profileId,
                'serial' => $serial
            ]
        );

        $join = $stmt->fetch();
        $builder->close();

        if (empty($join)) {
            return Http::status(402);
        }

        if ((int)$join['payments'] === 0) {
            return Http::status(402);
        }

        return Http::status(200, $join);
    }
}
