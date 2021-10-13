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

class FakeCustomerReportController implements IReport
{

    protected $em;
    protected $upload;
    private $defaultOrder = 'up.timestamp';

    public function __construct(EntityManager $em)
    {
        $this->em     = $em;
        $this->upload = new _UploadStorageController($em);
    }

    public function getData(array $serial, \DateTime $start, \DateTime $end, array $options, array $user)
    {
        $customers = $options['data'];

        if (!empty($customers)) {
            foreach ($customers as $key => $person) {
                foreach ($person as $k => $v) {
                    if (is_numeric($v)) {
                        if ($k === 'lat' || $k === 'lng') {
                            $people[$key][$k] = (float)$v;
                        } elseif ($k !== 'phone') {
                            $people[$key][$k] = (int)round($v);
                        }
                    }
                }
            }

            $return = [
                'table'    => $customers,
                'has_more' => false,
                'total'    => sizeof($customers)
            ];

            if ($options['export'] === true) {
                $headers = [
                    'Id',
                    'First Name',
                    'Last Name',
                    'Phone',
                    'Email',
                    'Join Date',
                    'Connected At',
                    'Last Seen At',
                    'Age Range',
                    'Postcode',
                    'Birth Month',
                    'Birth Day',
                    'Country',
                    'Verified',
                    'Gender',
                    'Time Spent',
                    'Total Uploaded',
                    'Total Downloaded',
                    'Total Connections',
                    'Latitude',
                    'Longitude'
                ];

                $newCSV = $this->export(
                    [
                        'serial' => $serial[0],
                        'type'   => 'customerFake',
                        'start'  => $start,
                        'end'    => $end
                    ],
                    $customers,
                    $headers
                );

                $retrieveFile = $this->fileExists($newCSV, 'customerFake');

                $return['export'] = 'FAILED_TO_GENERATE_REPORT';

                if (is_string($retrieveFile)) {
                    $return['export'] = $retrieveFile;
                }
            }

            return $return;
        }

        return [];
    }

    public function plotData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        foreach ($options['data'] as $key => $customer) {
            $options['data'][$key]['timestamp'] = $customer['timestamp']->getTimestamp();
        }


        return $options['data'];
    }

    public function totalData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        $customers = $options['data'];
        $returns   = rand(0, sizeof($customers));

        $returnStructure = [
            'totalUpload'      => 0,
            'totalDownload'    => 0,
            'customers'        => sizeof($customers),
            'timespent'        => 0,
            'averageUp'        => 0,
            'uniqueUsers'      => 1,
            'registrations'    => rand(0, sizeof($customers)),
            'averageDown'      => 0,
            'averageTime'      => 0,
            'totalConnections' => 0,
            'return'           => $returns,
            'male'             => 0,
            'female'           => 0,
            'other'            => 0,

        ];

        foreach ($customers as $key => $customer) {
            $returnStructure['totalUpload']      += $customer['totalUpload'];
            $returnStructure['totalDownload']    += $customer['totalDownload'];
            $returnStructure['totalConnections'] += $customer['totalConnections'];
            $returnStructure['timespent']        += $customer['uptime'];
            if ($customer['gender'] === 'm') {
                $returnStructure['male'] += 1;
            } elseif ($customer['gender'] === 'f') {
                $returnStructure['female'] += 1;
            } elseif ($customer['gender'] === 'o') {
                $returnStructure['other'] += 1;
            }
        }

        $returnStructure['averageUp']   = $returnStructure['totalUpload'] / $returnStructure['totalConnections'];
        $returnStructure['averageDown'] = $returnStructure['totalDownload'] / $returnStructure['totalConnections'];
        $returnStructure['averageTime'] = round($returnStructure['uptime'] / $returnStructure['totalConnections'],
            0);

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
            $customer = [
                'id'       => $faker->uuid,
                'first'    => $faker->firstName,
                'last'     => $faker->lastName,
                'phone'    => $faker->phoneNumber,
                'email'    => $faker->email,
                'ageRange' => '',
                'postcode' => $faker->postcode,
                'country'  => $faker->countryCode,
                'verified' => $faker->randomElement([true, false]),
                'gender'   => $faker->randomElement(['m', 'f', 'o'])
            ];

            $customer['timestamp']   = $faker->dateTimeBetween($startTime, $endTime);
            $customer['connectedAt'] = $faker->dateTimeBetween($customer['timestamp'], $endTime);
            $customer['lastseenAt']  = $faker->dateTimeBetween($customer['timestamp'], $endTime);
            $customer['lastupdate']  = $faker->dateTimeBetween($customer['timestamp'], $endTime);
            $customer['uptime']      = $customer['timestamp']->diff($customer['lastupdate'])->s;

            $customer['totalUpload']      = $faker->numberBetween(10, 99999999);
            $customer['totalDownload']    = $faker->numberBetween(10, 99999999);
            $customer['totalConnections'] = $faker->numberBetween(1, 10);

            $customer['lat'] = $faker->latitude;
            $customer['lng'] = $faker->longitude;

            $birth      = $faker->dateTimeThisCentury;
            $birthMonth = $birth->format('m');
            $birthDay   = $birth->format('d');

            $customer['birthMonth'] = $birthMonth;
            $customer['birthDay']   = $birthDay;

            $dataArray[] = $customer;
        }

        return $dataArray;
    }
}