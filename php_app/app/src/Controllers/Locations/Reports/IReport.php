<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 01/05/2017
 * Time: 10:52
 */

namespace App\Controllers\Locations\Reports;

interface IReport
{

    public function getData(array $serial, \DateTime $start, \DateTime $end, array $options, array $user);

    public function plotData(array $serial, \DateTime $start, \DateTime $end, array $options): array;

    public function totalData(array $serial, \DateTime $start, \DateTime $end, array $options): array;

    public function getDefaultOrder(): string;

    public function setDefaultOrder(array $options);
}
