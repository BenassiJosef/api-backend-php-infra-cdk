<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/07/2017
 * Time: 09:07
 */

namespace App\Controllers\Integrations\ChargeBee;

use App\Models\Integrations\ChargeBee\Coupon;
use App\Models\Integrations\ChargeBee\CouponApplied;
use Doctrine\ORM\EntityManager;

class _ChargeBeeCouponController
{
    private $errorHandler;
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->errorHandler = new _ChargeBeeHandleErrors();
        $this->em           = $em;
    }

    public function createFromWebHook(array $couponEvent)
    {
        $newCoupon = new Coupon();
        foreach ($couponEvent as $key => $value) {
            $newCoupon->$key = $value;
        }
        $this->em->persist($newCoupon);
        $this->em->flush();
    }

    public function updateFromWebHook(array $couponEvent)
    {
        $coupon = $this->em->getRepository(Coupon::class)->findOneBy([
            'id' => $couponEvent['id']
        ]);

        if (is_null($coupon)) {
            $this->createFromWebHook($couponEvent);
        } else {
            foreach ($couponEvent as $key => $value) {
                $coupon->$key = $value;
            }
            $this->em->flush();
        }
    }

    public function deleteFromWebHook(array $couponEvent)
    {
        $coupon = $this->em->getRepository(Coupon::class)->findOneBy([
            'id' => $couponEvent['id']
        ]);

        if (is_object($coupon)) {
            $this->em->createQueryBuilder()
                ->delete(CouponApplied::class, 'applied')
                ->where('applied.couponId = :id')
                ->setParameter('id', $couponEvent['id'])
                ->getQuery()
                ->execute();

            $this->em->remove($coupon);
            $this->em->flush();
        }
    }
}
