<?php

namespace App\Package\Nearly;

use App\Models\Nearly\Impressions;
use App\Models\Nearly\ImpressionsAggregate;
use DateTime;
use Doctrine\ORM\EntityManager;

class NearlyImpression
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * MarketingController constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager

    ) {
        $this->entityManager = $entityManager;
    }

    public function load(NearlyOutput $output)
    {
        if ($output->shouldAutoAuth()) {
            return;
        }
        $profile = $output->getProfile();
        $serial = $output->getLocation()->getSerial();
        $impression = new Impressions(is_null($profile) ? null : $profile->getId(), $serial);
        $this->entityManager->persist($impression);

        $output->setImpressionId($impression->id);
        $this->impressionAggregate($serial);
    }

    public function convertImpression(string $id, int $profileId)
    {
        /**
         *@var Impressions $converted
         */
        $converted = $this->entityManager->getRepository(Impressions::class)->find($id);

        if (!is_null($converted)) {
            $converted->profileId         = $profileId;
            $converted->converted         = true;
            $converted->conversionCreated = new DateTime();
            $this->entityManager->persist($converted);
            $this->impressionAggregate($converted->getSerial());
        }
    }

    public function impressionAggregate(string $serial)
    {
        $hour  = date('H');
        $day   = date('j');
        $week  = date('W');
        $month = date('m');
        $year  = date('Y');

        $date      = new DateTime();
        $formatted = $date->format('Y-m-d H:00:00');

        $date = new DateTime($formatted);

        $impressionAggregate = $this->entityManager->getRepository(ImpressionsAggregate::class)->findOneBy([
            'serial' => $serial,
            'hour'   => $hour,
            'week'   => $week,
            'day'    => $day,
            'month'  => $month,
            'year'   => $year
        ]);

        if (is_null($impressionAggregate)) {
            $impressionAggregate              = new ImpressionsAggregate(
                $serial,
                $year,
                $month,
                $week,
                $day,
                $hour,
                $date
            );
            $impressionAggregate->impressions += 1;
        } else {
            $impressionAggregate->impressions += 1;
        }
        $this->entityManager->persist($impressionAggregate);
        $this->entityManager->flush();
    }
}
