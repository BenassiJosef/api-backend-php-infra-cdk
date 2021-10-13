<?php

/**
 * Created by jamieaitken on 07/06/2018 at 10:49
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\RabbitMQ;


use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\Uploads\_UploadStorageController;
use App\Utils\Factories\Reports\ReportFactory;
use App\Utils\Factories\Reports\ReportProducer;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class FileExportWorker
{
	protected $mail;
	protected $em;
	protected $upload;
	protected $mp;

	public function __construct(_MailController $mail, EntityManager $em)
	{
		$this->em     = $em;
		$this->mail   = $mail;
		$this->mp     = new _Mixpanel();
		$this->upload = new _UploadStorageController($em);
	}

	public function runWorkerRoute(Request $request, Response $response)
	{
		$legacy = (bool)$request->getParsedBodyParam('legacy', false);
		if ($legacy) {
			$this->runWorker($request->getParsedBody());
		}

		$this->em->clear();
	}

	public function runWorker(array $body)
	{
		try {
			$reportProducer = new ReportProducer(new ReportFactory($this->em));

			$report = $reportProducer->produce($body['kind'], $body['optionalData']);

			$start = new \DateTime($body['dateStart']['date']);
			$end   = new \DateTime($body['dateEnd']['date']);

			$data = $report->export($body['serial'], $start, $end, $body['optionalData']);

			$kind = $body['kind'];

			$path = $kind . '/' . $body['serial'][0] . '/' . $start->getTimestamp() . '_' . $end->getTimestamp();
			$this->upload->generateCsv($report->getExportHeaders(), $data, $path, $kind);

			$link = $this->upload->checkFile($path, $kind);
			$link = substr($link['message'], 0, strlen($link['message']) - 4);


			$send = $this->mail->send(
				[
					[
						'to'   => $body['user']['email'],
						'name' => $body['user']['first'] . ' ' . $body['user']['last']
					]
				],
				[
					'filePath' => $link,
					'admin'    => $body['user']['admin']
				],
				'FileDownload',
				'Data Export(' . ucfirst($body['kind']) . ' for ' . $body['serial'][0] . ')'
			);
			if ($send['status'] !== 200) {
				$this->mp->track('FILE_EXPORT_NOT_SENT', [
					'body'   => $body,
					'reason' => $send['message']
				]);
			}
		} catch (\Exception $exception) {
			newrelic_notice_error('File Export Exception', $exception);
		}
	}
}
