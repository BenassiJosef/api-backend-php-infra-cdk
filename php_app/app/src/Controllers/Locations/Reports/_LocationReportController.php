<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 31/03/2017
 * Time: 14:54
 */

namespace App\Controllers\Locations\Reports;

use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Controllers\Integrations\Uploads\_UploadStorageController;
use App\Utils\CacheEngine;
use App\Utils\Factories\Reports\ReportFactory;
use App\Utils\Factories\Reports\ReportProducer;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _LocationReportController
{
	protected $em;
	protected $upload;
	protected $connectCache;

	public function __construct(EntityManager $em)
	{
		$this->em           = $em;
		$this->upload       = new _UploadStorageController($this->em);
		$this->connectCache = new CacheEngine(getenv('CONNECT_REDIS'));
	}

	public function generateReportRoute(Request $request, Response $response)
	{

		$serial           = [$request->getAttribute('serial')];
		$kind             = $request->getAttribute('kind');
		$start            = $request->getAttribute('start');
		$end              = $request->getAttribute('end');
		$user             = $request->getAttribute('user');
		$additionalParams = $request->getQueryParams();
		$allowedParams    = [
			'offset',
			'export',
			'table',
			'line',
			'type',
			'sort',
			'order',
			'totals',
			'pageId',
			'campaignId',
			'eventType'
		];
		$allowedKinds     = [
			'devices',
			'devicesFake',
			'registrations',
			'registrationsFake',
			'payments',
			'paymentsFake',
			'customer',
			'customerFake',
			'connections',
			'connectionsFake',
			'overview',
			'gdpr',
			'marketingOptOut',
			'browser',
			'os',
			'splashimpressions',
			'nearlystories',
			'marketingdeliverable'
		];
		$send             = Http::status(400, 'INVALID_TIMESTAMP');

		foreach ($additionalParams as $key => $param) {
			if (!in_array($key, $allowedParams)) {
				unset($additionalParams[$key]);
			}
		}

		if (!in_array($kind, $allowedKinds)) {
			return Http::status(404, 'INVALID_KIND');
		}

		if (is_null($serial)) {
			return Http::status(204, 'NO_SUITABLE_DATA_FOR_THIS_SERIAL_WITHIN_THIS_TIME_FRAME_COULD_BE_LOCATED');
		}

		if (strlen($start) === 10 && strlen($end) === 10) {
			$send = $this->determineChartKind($kind, $serial, $start, $end, $additionalParams, $user);
		}

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function     determineChartKind(
		string $kind,
		array $serial,
		int $dateTimeStart,
		int $dateTimeEnd,
		array $extraData,
		array $user
	) {
		$dateStart = new \DateTime();
		$dateEnd   = new \DateTime();

		$fakeReportKinds = [
			'devicesFake',
			'registrationsFake',
			'paymentsFake',
			'customerFake',
			'connectionsFake'
		];

		$dateStart->setTimestamp($dateTimeStart);
		$dateEnd->setTimestamp($dateTimeEnd);

		$data = [];

		if ($dateStart > $dateEnd) {
			return Http::status(409, 'END_DATE_IS_GREATER_THAN_START_DATE');
		}

		$diffCheck = $dateStart->diff($dateEnd);

		if ($diffCheck->days > 365) {
			$differenceToYearInDays = $diffCheck->days - 365;
			$dateEnd->sub(new \DateInterval('P' . $differenceToYearInDays . 'D'));

			$data['errors'][] = 'DATE_RANGE_CAN_NOT_BE_GREATER_THAN_YEAR';
		}

		$offset     = 0;
		$sort       = 'DESC';
		$report     = null;
		$group      = null;
		$export     = false;
		$lineExists = false;
		$table      = false;
		$totals     = false;

		$sortKeys = [
			'down' => 'DESC',
			'up'   => 'ASC'
		];

		$allowedOrderBy = [
			'pId'            => 'ud.profileId',
			'dl'             => 'totalDownload',
			'ul'             => 'totalUpload',
			'cons'           => 'totalConnections',
			'ut'             => 'uptime',
			'jd'             => 'joinDate',
			'udts'           => 'ud.timestamp',
			'upts'           => 'up.timestamp',
			'upcd'           => 'upa.creationdate',
			'lud'            => 'ud.lastupdate',
			'cat'            => 'connectedAt',
			'sat'            => 'lastseenAt',
			'deviceType'     => 'deviceType',
			'deviceBrand'    => 'deviceBrand',
			'deviceModel'    => 'deviceModel',
			'browserType'    => 'browserType',
			'browserName'    => 'browserName',
			'browserVersion' => 'browserVersion',
			'osName'         => 'osName',
			'osVersion'      => 'osVersion'
		];

		$allowedTypes = [
			'g'          => 'gender',
			'o'          => 'origin',
			'validation' => 'validation',
			'marketing'  => 'marketing',
			'review'     => 'review'
		];

		$optionalData = [
			'offset' => $offset,
			'export' => $export,
			'sort'   => $sort
		];

		if (array_key_exists('export', $extraData)) {
			$optionalData['export'] = (bool)$extraData['export'];
			/*if ($optionalData['export'] === true) {
                $check = $this->fileExists($kind . '/' . $serial[0] . '/' . $dateStart->getTimestamp() . '_' .
                    $dateEnd->getTimestamp(), $kind);
                if (!is_bool($check) && (new \DateTime() > $dateEnd)) {
                    return Http::status(200, $check);
                }
            }*/
		}

		if (array_key_exists('offset', $extraData)) {
			$optionalData['offset'] = $extraData['offset'];
		}

		$optionalData['type']  = $this->validateKey('type', $extraData, $allowedTypes);
		$optionalData['order'] = $this->validateKey('order', $extraData, $allowedOrderBy);
		$optionalData['sort']  = $this->validateKey('sort', $extraData, $sortKeys);

		if (array_key_exists('line', $extraData)) {
			$lineExists = (bool)$extraData['line'];

			if ($lineExists === true) {
				$newGrouping              = new _ReportChartGenerator();
				$grouping                 = $newGrouping->generateGrouping($dateStart, $dateEnd);
				$optionalData['grouping'] = $grouping;
			}
		}

		if (array_key_exists('table', $extraData)) {
			$table = (bool)$extraData['table'];
		}

		if (array_key_exists('totals', $extraData)) {
			$totals = (bool)$extraData['totals'];
		}

		if ($kind === 'overview') {
			$registrations = new _RegistrationReportController($this->em);
			$customers     = new _CustomerReportController($this->em);
			$connections   = new _ConnectionReportController($this->em);

			$data['registrations'] = $registrations->totalData($serial, $dateStart, $dateEnd, $optionalData);
			$data['customers']     = $customers->totalData($serial, $dateStart, $dateEnd, $optionalData);
			$data['connections']   = $connections->totalData($serial, $dateStart, $dateEnd, $optionalData);

			return Http::status(200, $data);
		}


		$reportProducer = new ReportProducer(
			new ReportFactory($this->em)
		);
		$report         = $reportProducer->produce($kind, $optionalData);

		if (isset($extraData['pageId']) && is_a($report, NearlyStoriesReport::class)) {
			$optionalData['pageId'] = $extraData['pageId'];
		}


		if (in_array($kind, $fakeReportKinds)) {
			$optionalData['data'] = $report->generateData($dateStart, $dateEnd);
		}


		if ($table === true) {
			if ($optionalData['export'] === true) {

				$client = new QueueSender();
				$client->sendMessage([
					'serial'       => $serial,
					'dateStart'    => $dateStart,
					'dateEnd'      => $dateEnd,
					'optionalData' => $optionalData,
					'user'         => $user,
					'kind'         => $kind,
					'legacy' => true
				], QueueUrls::FILE_EXPORT);

				return Http::status(200, 'EXPORT_WILL_BE_READY_SHORTLY');
			} else {
				$data = $report->getData($serial, $dateStart, $dateEnd, $optionalData, $user);
			}
		}

		if ($lineExists === true) {
			$chartCache = new ChartReportCache($this->connectCache);
			$chartRef   = $chartCache->getOrSaveToCache(
				$serial,
				$kind,
				$report->getDefaultOrder(),
				$dateTimeStart,
				$dateTimeEnd
			);

			if (!is_bool($chartRef)) {
				$data['chart'] = $chartRef;
			} else {
				$plotData = $report->plotData($serial, $dateStart, $dateEnd, $optionalData);
				if (!empty($plotData)) {
					$data['chart'] = $newGrouping->addDataToGrouping(
						$plotData,
						$optionalData['grouping'],
						'timestamp'
					);
				} else {
					$data['chart'] = [];
				}
				if (is_array($data['chart'])) {
					$this->connectCache->save(
						'reports:' . implode(',', $serial) . ':'
							. $kind . ':' . $report->getDefaultOrder() . ':' . $dateTimeStart . '_' . $dateTimeEnd,
						$data['chart']
					);
				}
			}
		}

		if ($totals === true) {
			$data['totals'] = $report->totalData($serial, $dateStart, $dateEnd, $optionalData);
		}

		if (!empty($data)) {
			return Http::status(200, $data);
		}

		return Http::status(204, 'NO_SUITABLE_DATA_FOR_THIS_SERIAL_WITHIN_THIS_TIME_FRAME_COULD_BE_LOCATED');
	}

	private function fileExists(string $path, string $kind)
	{
		$fileCheck = $this->upload->checkFile($path, $kind);
		if ($fileCheck['status'] === 200) {
			return substr($fileCheck['message'], 0, strlen($fileCheck['message']) - 4);
		}

		return false;
	}

	private function validateKey($key, $passingInArray, $allowedArray)
	{
		if (array_key_exists($key, $passingInArray)) {
			if (array_key_exists($passingInArray[$key], $allowedArray)) {
				return $allowedArray[$passingInArray[$key]];
			}
		}

		return null;
	}
}
