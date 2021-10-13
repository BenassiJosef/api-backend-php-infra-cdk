<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 27/12/2016
 * Time: 15:18
 */

namespace App\Controllers\Registrations;

use App\Controllers\Integrations\Hooks\_HooksController;
use App\Controllers\Integrations\Mail\_MailController;
use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Integrations\SQS\QueueSender;
use App\Controllers\Integrations\SQS\QueueUrls;
use App\Controllers\Locations\Settings\Other\LocationOtherController;
use App\Models\Locations\LocationOptOut;
use App\Models\Locations\LocationSettings;
use App\Models\Marketing\MarketingOptOut;
use App\Models\UserData;
use App\Models\UserProfile;
use App\Models\ValidationSent;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _ValidationController
{

	protected $em;

	protected $mail;

	/**
	 * _ValidationController constructor.
	 * @param EntityManager $em
	 */

	public function __construct(EntityManager $em)
	{
		$this->em   = $em;
		$this->mail = new _MailController($this->em);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */

	public function getValidate(Request $request, Response $response, $args)
	{

		$id = $request->getAttribute('id');

		$send = $this->validate($id);

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function validate(string $profileId)
	{
		/**
		 * @var  UserProfile $profile
		 */
		$profile = $this->em->getRepository(UserProfile::class)->findOneBy([
			'verified_id' => $profileId
		]);

		if (is_null($profile)) {
			return Http::status(404, 'INVALID_ID');
		}

		if ($profile->verified === 1) {
			return Http::status(409, 'ALREADY_VALID');
		}

		$profile->verified = 1;
		$this->em->flush();

		/**
		 * DELETE ALL OCCURENCES OF THIS VERIFIED ID AS THESE ARE NOW INVALID
		 */

		$this->em->createQueryBuilder()
			->delete(UserProfile::class, 'p')
			->where('p.verified_id = :id')
			->andWhere('p.verified = 0')
			->setParameter('id', $profileId)
			->getQuery()
			->execute();

		/**
		 * SEND DATA OFF TO HOOKZ
		 */

		$serialArr = $this->em->createQueryBuilder()
			->select('u.serial')
			->from(UserData::class, 'u')
			->where('u.profileId = :profileId')
			->setParameter('profileId', $profile->id)
			->orderBy('u.timestamp', 'DESC')
			->setMaxResults(1)
			->getQuery()
			->getArrayResult();

		if (!empty($serialArr)) {
			$hook = new _HooksController($this->em);
			$hook->serialToHook($serialArr[0]['serial'], 'registration', $profile->zapierSerialize($serialArr[0]['serial']));

			$notificationPublisher = new QueueSender();

			$notificationPublisher->sendMessage([
				'notificationContent' => [
					'objectId' => $profile->id,
					'profileId' => $profile->id,
					'title'    => 'Captured Validation',
					'kind'     => 'capture_validated',
					'link'     => '/analytics/registrations',
					'serial'   => $serialArr[0]['serial'],
					'message' => $profile->email . ' just verified their email address'
				]
			], QueueUrls::NOTIFICATION);
		}

		$mp = new _Mixpanel();
		$mp->register('serial', $serialArr[0]['serial'])->track('validation_complete', $profile->getArrayCopy());

		return Http::status(200, 'ACCOUNT_VALIDATED');
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return mixed
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Twig_Error_Loader
	 * @throws \Twig_Error_Runtime
	 * @throws \Twig_Error_Syntax
	 * @throws \phpmailerException
	 */

	public function postValidate(Request $request, Response $response)
	{

		$body   = $request->getParsedBody();
		$id     = $body['id'];
		$serial = $body['serial'];
		$valid  = $this->sendValidate($serial, $id);

		$mp = new _Mixpanel();
		$mp->register('serial', $serial)->track('validation_send', $valid);

		$this->em->clear();

		return $response->withJson($valid);
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $args
	 * @return mixed
	 */

	public function backlogValidation(Request $request, Response $response, $args)
	{
		/* DOES NOT FUCKING WORK
                $serials = [
                    'AWWR5WSRLM4W', 'YXSABLW7ST29', 'ZNK515ARHWIP', 'PX2FEHOUBWO5', 'XV4W9AHLBZR7', 'P6XRCP3LSUHU'
                ];
    
                $qb      = $this->em->createQueryBuilder();
                $results = $qb->select('u')
                    ->from(UserData::class, 'u')
                    ->join(UserProfile::class, 'p', 'WITH', 'p.id = u.profileId')
                    ->where('p.verified = 0')
                    ->andWhere('u.serial IN (:serials)')
                    ->setParameter('serials', $serials)
                    ->groupBy('p.id')
                    ->getQuery()
                    ->getArrayResult();
    
                foreach ($results as $profile) {
                    $this->sendValidate($profile['serial'], $profile['profileId']);
                }
    
                return $response->write(
                    json_encode($results)
                );
        */
	}

	/**
	 * @param string $serial
	 * @param string $id
	 * @return array
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Twig_Error_Loader
	 * @throws \Twig_Error_Runtime
	 * @throws \Twig_Error_Syntax
	 * @throws \phpmailerException
	 */

	public function sendValidate(string $serial = '', string $id)
	{

		$send = [
			'message' => '',
			'status'  => 400
		];

		$profile = $this->em->createQueryBuilder()
			->select('r.verified, r.email, r.first, r.last, r.id')
			->from(UserProfile::class, 'r')
			->where('r.id = :id')
			->setParameter('id', $id)
			->setMaxResults(1)
			->getQuery()
			->getArrayResult();

		if (empty($profile)) {

			$send['message'] = ['PROFILE_INVALID'];

			return $send;
		}

		$profile = $profile[0];
		if ($profile['verified'] === 1) {
			//EMAIL Has been verified
			$send['message'] = 'PROFILE_VERIFIED';

			return $send;
		}

		$networkSettings = $this->em->createQueryBuilder()
			->select('u.alias, u.other')
			->from(LocationSettings::class, 'u')
			->where('u.serial = :s')
			->setParameter('s', $serial)
			->getQuery()
			->getArrayResult()[0];

		if (empty($networkSettings)) {
			$send['message'] = ['code' => 'INVALID_SERIAL', 'serial' => $serial, 'id' => $id];

			return $send;
		}

		$newLocationOther = new LocationOtherController($this->em);
		$other            = $newLocationOther->getNearlyOther($serial, $networkSettings['other'])['message'];
		if ((bool) $other['validation'] === false) {
			$send['message'] = ['code' => 'VALIDATION_NOT_TURNED_ON'];
			$send['status']  = 204;

			return $send;
		}

		if (is_null($networkSettings['alias'])) {
			$networkSettings['alias'] = $serial;
		}


		$dataOptOutCheck = $this->em->createQueryBuilder()
			->select('u.id')
			->from(LocationOptOut::class, 'u')
			->where('u.profileId = :profileId')
			->andWhere('u.serial = :serial')
			->andWhere('u.deleted = :false')
			->setParameter('profileId', $id)
			->setParameter('serial', $serial)
			->setParameter('false', false)
			->getQuery()
			->getArrayResult();

		if (!empty($dataOptOutCheck)) {
			$send['message'] = 'USER_OPT_OUT';

			return $send;
		}

		$marketingOptOutCheck = $this->em->createQueryBuilder()
			->select('u.id')
			->from(MarketingOptOut::class, 'u')
			->where('u.uid = :profileId')
			->andWhere('u.serial = :serial')
			->andWhere('u.optOut = :true')
			->setParameter('profileId', $id)
			->setParameter('serial', $serial)
			->setParameter('true', true)
			->getQuery()
			->getArrayResult();

		if (!empty($marketingOptOutCheck)) {
			$send['message'] = 'USER_OPT_OUT';

			return $send;
		}

		$send['alias'] = $networkSettings['alias'];
		$sent          = $this->mailChange($serial, $networkSettings['alias'], $profile);


		if ($sent['status'] === 200) {
			$send['optOut']  = $dataOptOutCheck;
			$send['message'] = 'MESSAGE_SENT';
			$send['sent']    = true;
			$send['status']  = 200;
		}

		return $send;
	}

	/**
	 * @param string $serial
	 * @param string $alias
	 * @param array $profile
	 * @return array
	 * @throws \Doctrine\ORM\ORMException
	 * @throws \Doctrine\ORM\OptimisticLockException
	 * @throws \Twig_Error_Loader
	 * @throws \Twig_Error_Runtime
	 * @throws \Twig_Error_Syntax
	 * @throws \phpmailerException
	 */

	public function mailChange(string $serial = '', string $alias = '', array $profile = [])
	{

		$subject = 'Email Validation for ' . $alias;

		$name = '';

		if (!is_null($profile['first'])) {
			$name .= $profile['first'];
		}

		if (!is_null($profile['last'])) {
			$name .= ' ' . $profile['last'];
		}

		if (empty($name)) {
			$name = null;
		}

		$sendTo = [
			[
				'name' => $profile['first'] . ' ' . $profile['last'],
				'to'   => $profile['email']
			]
		];

		$args = [
			'alias'     => $alias,
			'title'     => 'Location Online',
			'serial'    => $serial,
			'md5Email'  => md5($profile['email']),
			'profileId' => $profile['id']
		];

		$log            = new ValidationSent();
		$log->profileId = $profile['id'];
		$log->email     = $profile['email'];
		$log->timestamp = new \DateTime();

		$args['state'] = sha1($profile['id'] . $profile['email']);

		$this->em->persist($log);
		$this->em->flush();

		$send = $this->mail->send($sendTo, $args, 'RegistrationValidation', $subject);

		return $send;
	}
}
