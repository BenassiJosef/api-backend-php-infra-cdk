<?php

namespace App\Controllers\Marketing;

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 16/06/2017
 * Time: 13:07
 */

use App\Models\BouncedEmails;
use App\Models\Marketing\MarketingDeliverable;
use App\Models\Marketing\MarketingDeliverableEvent;

use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Exception;
use Slim\Http\Response;
use Slim\Http\Request;

class _MarketingCallBackController
{
	protected $em;

	const INSERT_BOUNCE_QUERY = "INSERT INTO bounced_emails 
(email, bounced_at) 
VALUES (:email, NOW()) 
ON DUPLICATE KEY UPDATE email = email";

	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}

	public function bouncedEmail(string $email)
	{
		$statement = $this
			->em
			->getConnection()
			->prepare(self::INSERT_BOUNCE_QUERY);

		$statement->bindValue(':email', $email);

		$statement->execute();
	}

	public function sesCallback(Request $request, Response $response)
	{

		$body = json_decode($request->getBody(), true);
		if (array_key_exists('Type', $body)) {
			if ($body['Type'] === 'SubscriptionConfirmation') {
				$arr2 = str_split($body['SubscribeURL'], 128);
				foreach ($arr2 as $key => $arr) {
					newrelic_add_custom_parameter('SubscriptionConfirmation' . $key, $arr);
				}
			}
		}



		$eventType = $this->eventMapper($body['eventType']);

		$mail      = $body['mail'];
		$messageId = $mail['messageId'];
		$params = $this->getSesMessageTage($mail['headers']);

		$this->em->beginTransaction();
		$find = $this->em->getRepository(MarketingDeliverable::class)->findOneBy([
			'type'      => 'email',
			'messageId' => $messageId
		]);
		if (is_null($find)) {
			$find = new MarketingDeliverable(
				'email',
				$messageId,
				(int)$params['profileId'],
				$params['serial'],
				$params['templateType'],
				$params['campaignId']
			);
			$this->em->persist($find);
			$this->em->flush();
		}

		$newDeliverableEvent = new MarketingDeliverableEvent($find->getId(), $eventType, strtotime($mail['timestamp']), '');
		if ($eventType === 'complaint') {
			$newDeliverableEvent->eventSpecificInfo = $body['complaint']['complaintFeedbackType'];
		} elseif ($eventType === 'bounce') {
			$newDeliverableEvent->eventSpecificInfo = $body['bounce']['bounceType'];
			if ($newDeliverableEvent->eventSpecificInfo === 'Permanent') {
				$email = $mail['destination'][0];
				$this->bouncedEmail($email);
			}
		} elseif ($eventType === 'dropped') {
			//  $newDeliverableEvent->eventSpecificInfo = $event['reason'];
		} elseif ($eventType === 'deferred') {
			//   $newDeliverableEvent->eventSpecificInfo = $event['attempt'];
		} elseif ($eventType === 'click') {
			$newDeliverableEvent->eventSpecificInfo = $body['click']['link'];
		}
		$this->em->persist($newDeliverableEvent);
		$this->em->flush();
		$this->em->commit();
		return $response->withJson($request->getParsedBody(), 200);
	}

	public function eventMapper(string $event)
	{
		$event = strtolower($event);
		if ($event === 'delivery') {
			return 'delivered';
		}
		if ($event === 'send') {
			return 'processed';
		}
		if ($event === 'sent') {
			return 'processed';
		}
		return $event;
	}

	public function getSesMessageTage(array $headers)
	{
		$key   = array_search('X-SES-MESSAGE-SET', array_column($headers, 'name'));
		$value = json_decode($headers[$key]['value'], true);
		return [
			'profileId'    => isset($value['profileId']) ? (int)$value['profileId'] : '',
			'serial'       => isset($value['serial']) ? $value['serial'] : '',
			'templateType' => isset($value['templateType']) ? $value['templateType'] : '',
			'campaignId'   => isset($value['campaignId']) ? $value['campaignId'] : '',
		];
	}


	public function sesDelivery(array $mail)
	{
	}

	public function insertEmailCallBackRoute(Request $request, Response $response)
	{
		$send = $this->insertEmailCallBack($request->getParsedBody());

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function insertSmsCallBackRoute(Request $request, Response $response)
	{
		$send = $this->insertSmsCallBack($request->getParsedBody(), $request->getQueryParams());

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function insertEmailCallBack(array $events)
	{
		foreach ($events as $event) {


			if ($event['event'] === 'bounce') {
				continue;
			}

			$find = $this->em->getRepository(MarketingDeliverable::class)->findOneBy(
				[
					'type'      => 'email',
					'messageId' => $event['sg_message_id']
				]
			);

			if (is_null($find)) {
				try {
					$find = new MarketingDeliverable(
						'email',
						$event['sg_message_id'],
						isset($event['profileId']) ? (int)$event['profileId'] : null,
						isset($event['serial']) ? $event['serial'] : '',
						isset($event['templateType']) ? $event['templateType'] : '',
						isset($event['campaignId']) ? $event['campaignId'] : ''
					);
					$this->em->persist($find);
					$this->em->flush();
				} catch (\Throwable $t) {
					if (extension_loaded('newrelic')) {
						newrelic_add_custom_parameter('email', $event['email']);
						newrelic_add_custom_parameter('event', json_encode($event));
						newrelic_notice_error($t);
					}
				}
			}

			$newDeliverableEvent = new MarketingDeliverableEvent($find->getId(), $event['event'], $event['timestamp'], '');
			if ($event['event'] === 'dropped' || $event['event'] === 'bounce') {
				$newDeliverableEvent->eventSpecificInfo = $event['reason'];
			} else if ($event['event'] === 'deferred') {
				$newDeliverableEvent->eventSpecificInfo = $event['attempt'];
			} else if ($event['event'] === 'click') {
				$newDeliverableEvent->eventSpecificInfo = $event['url'];
			}

			$this->em->persist($newDeliverableEvent);
		}
		$this->em->flush();

		return Http::status(200);
	}

	public function insertSmsCallBack(array $body, array $queryParams)
	{

		$find = $this->em->getRepository(MarketingDeliverable::class)->findOneBy(
			[
				'type'      => 'sms',
				'messageId' => $body['MessageSid']
			]
		);

		if (is_null($find)) {
			$find = new MarketingDeliverable(
				'sms',
				$body['MessageSid'],
				(int)$queryParams['profileId'],
				$queryParams['serial'],
				$queryParams['templateType'],
				$queryParams['campaignId']
			);
			$this->em->persist($find);
			$this->em->flush();
		}

		$newDateTime = new \DateTime();
		$timeStamp   = $newDateTime->getTimestamp();

		$newDeliverableEvent = new MarketingDeliverableEvent(
			$find->getId(),
			$this->eventMapper($body['SmsStatus']),
			$timeStamp,
			''
		);

		$this->em->persist($newDeliverableEvent);

		$this->em->flush();

		return Http::status(200);
	}
}
