<?php
/**
 * Created by jamieaitken on 12/04/2018 at 14:54
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\FakeReports;


use App\Controllers\Integrations\Uploads\_UploadStorageController;
use App\Controllers\Locations\Reports\IReport;
use Doctrine\ORM\EntityManager;
use Faker\Factory;

class FakeConnectionReportController implements IReport
{

    protected $em;
    protected $upload;
    private $defaultOrder = 'ud.timestamp';

    public function __construct(EntityManager $em)
    {
        $this->em     = $em;
        $this->upload = new _UploadStorageController($em);
    }

    public function getData(array $serial, \DateTime $start, \DateTime $end, array $options, array $user)
    {
        $connections = $options['data'];

        foreach ($connections as $key => $connection) {
            foreach ($connection as $k => $v) {
                if (is_numeric($v)) {
                    $connectedUsers[$key][$k] = (int)round($v);
                }
            }
        }

        $return = [
            'table'    => $connections,
            'has_more' => false,
            'total'    => count($connections),
        ];

        if ($options['export'] === true) {
            $newCSV = $this->export(
                [
                    'serial' => $serial[0],
                    'type'   => 'connectionsFake',
                    'start'  => $start,
                    'end'    => $end
                ],
                $connections,
                [
                    'Id',
                    'Email',
                    'Total Uploaded',
                    'Total Downloaded',
                    'Up Time',
                    'Last Connected',
                    'Timestamp'
                ]
            );

            $retrieveFile = $this->fileExists($newCSV, 'connectionsFake');

            if (is_string($retrieveFile)) {
                $return['export'] = $retrieveFile;
            } else {
                $return['export'] = 'FAILED_TO_GENERATE_REPORT';
            }
        }

        return $return;
    }

    public function plotData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        foreach ($options['data'] as $key => $connection) {
            $options['data'][$key]['timestamp'] = $connection['timestamp']->getTimestamp();
        }

        return $options['data'];
    }

    public function totalData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        $connections = $options['data'];

        $returnStructure = [
            'totalUpload'       => 0,
            'totalDownload'     => 0,
            'totalConnections'  => 0,
            'uptime'            => 0,
            'uniqueConnections' => 1,
            'averageUp'         => 0,
            'averageDown'       => 0,
            'averageUpTime'     => 0
        ];

        foreach ($connections as $key => $connection) {
            $returnStructure['totalUpload']      += $connection['totalUpload'];
            $returnStructure['totalDownload']    += $connection['totalDownload'];
            $returnStructure['totalConnections'] += $connection['totalConnections'];
            $returnStructure['uptime']           += $connection['uptime'];
        }

        $returnStructure['averageUp']     = $returnStructure['totalUpload'] / $returnStructure['totalConnections'];
        $returnStructure['averageDown']   = $returnStructure['totalDownload'] / $returnStructure['totalConnections'];
        $returnStructure['averageUpTime'] = round($returnStructure['uptime'] / $returnStructure['totalConnections'], 0);

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

            $dateTime = $faker->dateTimeBetween($startTime, $endTime);

            $connection = [
                'id'               => $faker->uuid,
                'email'            => $faker->email,
                'totalDownload'    => $faker->numberBetween(1, 9999999),
                'totalUpload'      => $faker->numberBetween(1, 9999999),
                'totalConnections' => $faker->numberBetween(1, 10),
                'uptime'           => (int)$dateTime->format('s'),
                'lastupdate'       => $dateTime,
                'timestamp'        => $faker->dateTimeBetween('-10 minute', 'now')
            ];

            $connection['lastupdate'] = $faker->dateTimeBetween($connection['timestamp'], $endTime);
            $connection['uptime']     = $connection['timestamp']->diff($connection['lastupdate'])->s;

            $dataArray[] = $connection;
        }

        return $dataArray;
    }
}