<?php

namespace App\Package\Marketing;

use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;

class MarketingBounces
{

	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * MarketingController constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(
		EntityManager $entityManager
	) {
		$this->entityManager = $entityManager;
	}

	const INSERT_BOUNCE_QUERY = "INSERT INTO bounced_emails 
(email, bounced_at) 
VALUES (:email, NOW()) 
ON DUPLICATE KEY UPDATE email = email";


	public function postBounce(Request $request, Response $response): Response
	{

		$email = $request->getParsedBodyParam('email', null);

		if (is_null($email)) {
			return $response->withStatus(403);
		}

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return $response
				->withStatus(403)
				->withJson('INVALID_EMAIL_FORMAT');
		}

		$statement = $this
			->entityManager
			->getConnection()
			->prepare(self::INSERT_BOUNCE_QUERY);

		$statement->bindValue(':email', $email);

		$statement->execute();

		return $response->withStatus(200);
	}
}
