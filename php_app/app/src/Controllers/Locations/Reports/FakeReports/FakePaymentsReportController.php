<?php
/**
 * Created by jamieaitken on 12/04/2018 at 14:55
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\FakeReports;


use App\Controllers\Integrations\Uploads\_UploadStorageController;
use App\Controllers\Locations\Reports\IReport;
use Doctrine\ORM\EntityManager;
use Faker\Factory;

class FakePaymentsReportController implements IReport
{

    protected $em;
    protected $upload;
    private $defaultOrder = 'upa.creationdate';

    public function __construct(EntityManager $em)
    {
        $this->em     = $em;
        $this->upload = new _UploadStorageController($em);
    }

    public function getData(array $serial, \DateTime $start, \DateTime $end, array $options, array $user)
    {
        $payments = $options['data'];

        if (!empty($payments)) {
            foreach ($payments as $key => $payment) {
                foreach ($payment as $k => $v) {
                    if (is_numeric($v)) {
                        if ($k === 'paymentAmount') {
                            $v                  = $v / 100;
                            $payments[$key][$k] = (float)$v;
                        } else {
                            $payments[$key][$k] = (int)$v;
                        }
                    }
                }
            }

            $return = [
                'table'    => $payments,
                'has_more' => false,
                'total'    => count($payments)
            ];


            if ($options['export'] === true) {
                $newCSV = $this->export(
                    [
                        'serial' => $serial[0],
                        'type'   => 'paymentsFake',
                        'start'  => $start,
                        'end'    => $end
                    ],
                    $payments,
                    ['Id', 'Transaction ID', 'Creation Date', 'Email', 'Duration', 'Payment Amount', 'Status', 'Serial']
                );

                $retrieveFile = $this->fileExists($newCSV, 'paymentsFake');

                if (is_string($retrieveFile)) {
                    $return['export'] = $retrieveFile;
                } else {
                    $return['export'] = 'FAILED_TO_GENERATE_REPORT';
                }
            }

            return $return;
        }

        return [];
    }

    public function plotData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        foreach ($options['data'] as $key => $payment) {
            $options['data'][$key]['timestamp'] = $payment['creationdate']->getTimestamp();
        }

        return $options['data'];
    }

    public function totalData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        $payments = $options['data'];

        $returnStructure = [
            'duration'      => 0,
            'paymentAmount' => 0,
            'avgDuration'   => 0,
            'avgAmount'     => 0,
            'payments'      => sizeof($payments),
            'repeatAmounts' => rand(0, sizeof($payments)),
        ];

        foreach ($payments as $key => $payment) {
            $returnStructure['duration']      += $payment['duration'];
            $returnStructure['paymentAmount'] += $payment['paymentAmount'];
        }

        $returnStructure['avgDuration'] = $returnStructure['duration'] / $returnStructure['payments'];
        $returnStructure['avgAmount']   = $returnStructure['paymentAmount'] / $returnStructure['payments'];

        return $returnStructure;
    }

    public function export(array $context, array $content, array $headers)
    {
        $kind = $context['type'];
        $path = $kind . '/' . $context['serial'] . '/' . $context['start']->getTimestamp() . '_' . $context['end']->getTimestamp();
        $this->upload->generateCsv($headers, $content, $path, $kind);

        return $path;
    }

    public function fileExists(string $path, string $kind)
    {
        $fileCheck = $this->upload->checkFile($path, $kind);
        if ($fileCheck['status'] === 200) {
            return substr($fileCheck['message'], 0, strlen($fileCheck['message']) - 4);
        }

        return false;
    }

    public function getDefaultOrder(): string
    {
        return $this->defaultOrder;
    }

    public function setDefaultOrder(array $options)
    {
        if (isset($options['order'])) {
            $this->defaultOrder = $options['order'];
        }
    }

    public function generateData(\DateTime $startTime, \DateTime $endTime)
    {
        $faker         = Factory::create('en_GB');
        $lengthOfArray = $faker->numberBetween(0, 49);

        $dataArray = [];

        for ($i = 0; $i <= $lengthOfArray; $i++) {
            $payment = [
                'id'            => $faker->uuid,
                'transactionId' => $faker->uuid,
                'creationdate'  => $faker->dateTimeBetween($startTime, $endTime),
                'email'         => $faker->email,
                'duration'      => $faker->numberBetween(1, 8760),
                'paymentAmount' => $faker->numberBetween(0, 7000),
                'status'        => $faker->randomElement([true, false]),
                'serial'        => ''
            ];

            $dataArray[] = $payment;
        }

        return $dataArray;
    }
}