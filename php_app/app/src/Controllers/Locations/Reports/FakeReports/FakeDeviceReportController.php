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

class FakeDeviceReportController implements IReport
{

    protected $em;
    protected $upload;
    private $defaultType = 'device';

    public function __construct(EntityManager $em)
    {
        $this->em     = $em;
        $this->upload = new _UploadStorageController($em);
    }

    public function getData(array $serial, \DateTime $start, \DateTime $end, array $options, array $user)
    {
        // TODO: Implement getData() method.
    }

    public function plotData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        // TODO: Implement plotData() method.
    }

    public function totalData(array $serial, \DateTime $start, \DateTime $end, array $options): array
    {
        // TODO: Implement totalData() method.
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
        return $this->defaultType;
    }

    public function setDefaultOrder(array $options)
    {
        if (isset($options['order'])) {
            $this->defaultType = $options['order'];
        }
    }

    public function generateData()
    {
        $faker         = Factory::create('en_GB');
        $lengthOfArray = $faker->numberBetween(0, 49);

        $dataArray = [];

        for ($i = 0; $i <= $lengthOfArray; $i++) {
            $device = [
                ''
            ];
        }
    }
}