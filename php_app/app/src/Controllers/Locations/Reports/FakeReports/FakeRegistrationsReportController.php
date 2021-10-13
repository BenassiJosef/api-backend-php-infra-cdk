<?php
/**
 * Created by jamieaitken on 12/04/2018 at 14:49
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\Reports\FakeReports;

use App\Controllers\Integrations\Uploads\_UploadStorageController;
use App\Controllers\Locations\Reports\IReport;
use Doctrine\ORM\EntityManager;
use Faker\Factory;

class FakeRegistrationsReportController implements IReport
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
        $registrations = $options['data'];

        if (!empty($registrations)) {
            foreach ($registrations as $key => $registration) {
                foreach ($registration as $k => $v) {
                    if (is_numeric($v) && $k !== 'phone') {
                        $registrations[$key][$k] = (int)round($v);
                    }
                }
            }

            $return = [
                'table'    => $registrations,
                'has_more' => false,
                'total'    => count($registrations)
            ];

            if ($options['export'] === true) {
                $headers = [
                    'ID',
                    'First name',
                    'Last name',
                    'Gender',
                    'Logins',
                    'Registrations',
                    'Join Date',
                    'Phone',
                    'Postcode',
                    'Age Range',
                    'Birth Month',
                    'Birth Day',
                    'Opt',
                    'Latitude',
                    'Longitude',
                    'Country',
                    'Type',
                    'Verified'
                ];


                $newCSV = $this->export(
                    [
                        'serial' => $serial[0],
                        'type'   => 'registrationsFake',
                        'start'  => $start,
                        'end'    => $end
                    ],
                    $registrations,
                    $headers
                );


                $retrieveFile = $this->fileExists($newCSV, 'registrationsFake');

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

    public
    function plotData(
        array $serial,
        \DateTime $start,
        \DateTime $end,
        array $options
    ): array {
        foreach ($options['data'] as $key => $registration) {
            $options['data'][$key]['timestamp'] = $registration['timestamp']->getTimestamp();
        }


        return $options['data'];
    }

    public
    function totalData(
        array $serial,
        \DateTime $start,
        \DateTime $end,
        array $options
    ): array {
        $registrations = $options['data'];

        $returnStructure = [
            'totalUpload'       => 0,
            'totalDownload'     => 0,
            'totalConnections'  => 0,
            'uptime'            => 0,
            'uniqueConnections' => 1,
            'averageUp'         => 0,
            'averageDown'       => 0,
            'averageUpTime'     => 0,
            'timeTaken'         => 0,
            'avgTimeTaken'      => 0,
            'verified'          => 0,
            'registrations'     => sizeof($options['data'])
        ];

        foreach ($registrations as $key => $registration) {
            $returnStructure['totalUpload']      += $registration['dataUp'];
            $returnStructure['totalDownload']    += $registration['dataDown'];
            $returnStructure['totalConnections'] += $registration['totalConnections'];
            $returnStructure['uptime']           += $registration['uptime'];
            $returnStructure['timeTaken']        += $registration['timeTaken'];
            $returnStructure['verified']         += $registration['verified'];
        }

        $returnStructure['averageUp']     = $returnStructure['totalUpload'] / $returnStructure['totalConnections'];
        $returnStructure['averageDown']   = $returnStructure['totalDownload'] / $returnStructure['totalConnections'];
        $returnStructure['averageUpTime'] = $returnStructure['uptime'] / $returnStructure['totalConnections'];
        $returnStructure['avgTimeTaken']  = $returnStructure['timeTaken'] / $returnStructure['totalConnections'];

        return $returnStructure;
    }

    public
    function export(
        array $context,
        array $content,
        array $headers
    ) {
        $kind = $context['type'];
        $path = $kind . '/' . $context['serial'] . '/' . $context['start']->getTimestamp() . '_' . $context['end']->getTimestamp();
        $this->upload->generateCsv($headers, $content, $path, $kind);

        return $path;
    }

    public
    function fileExists(
        string $path,
        string $kind
    ) {
        $fileCheck = $this->upload->checkFile($path, $kind);
        if ($fileCheck['status'] === 200) {
            return substr($fileCheck['message'], 0, strlen($fileCheck['message']) - 4);
        }

        return false;
    }

    public
    function getDefaultOrder(): string
    {
        return $this->defaultOrder;
    }

    public
    function setDefaultOrder(
        array $options
    ) {
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

            $registration = [
                'id'                => $faker->uuid,
                'first'             => $faker->firstName(),
                'last'              => $faker->lastName,
                'email'             => $faker->email,
                'gender'            => $faker->randomElement(['m', 'f']),
                'logins'            => $faker->numberBetween(0, 20),
                'timestamp'         => $faker->dateTimeBetween($startTime, $endTime),
                'phone'             => $faker->phoneNumber,
                'postcode'          => $faker->postcode,
                'ageRange'          => '',
                'lat'               => $faker->latitude,
                'lng'               => $faker->longitude,
                'country'           => $faker->countryCode,
                'type'              => $faker->randomElement(['paid', 'free']),
                'verified'          => $faker->randomElement([true, false]),
                'dataUp'            => $faker->numberBetween(10, 99999999),
                'dataDown'          => $faker->numberBetween(10, 99999999),
                'timeTaken'         => $faker->numberBetween(10, 9999),
                'totalConnections'  => $faker->numberBetween(1, 10),
                'uniqueConnections' => $faker->numberBetween(0, 1),
                'uptime'            => (int)$faker->dateTimeBetween($startTime, $endTime)->format('s')
            ];

            $birth      = $faker->dateTimeThisCentury;
            $birthMonth = $birth->format('m');
            $birthDay   = $birth->format('d');

            $registration['birthMonth'] = $birthMonth;
            $registration['birthDay']   = $birthDay;

            $dataArray[] = $registration;
        }

        return $dataArray;
    }
}