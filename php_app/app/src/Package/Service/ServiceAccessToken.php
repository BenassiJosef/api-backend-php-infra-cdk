<?php

namespace App\Package\Service;


use DateTime;

class ServiceAccessToken
{
	/**
	 * @var string
	 */
	private $token;

	/**
	 * @var string
	 */
	private $scope;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var DateTime
	 */
	private $expires;

	public function __construct(string $tokenJson)
	{
		$token = json_decode($tokenJson, true);
		$keys = [
			'access_token', 'scope', 'token_type', 'expires_in',
		];

		$this->token = $token['access_token'];
		$this->scope = $token['scope'];
		$this->type = $token['token_type'];
		$date = new DateTime('+' . $token['expires_in'] . ' seconds');
		$this->expires = $date;
	}

	public function getToken(): string
	{
		return $this->token;
	}

	public function getScope(): string
	{
		return $this->scope;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getExpires(): DateTime
	{
		return $this->expires;
	}
}
