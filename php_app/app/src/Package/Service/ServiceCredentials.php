<?php

namespace App\Package\Service;


class ServiceCredentials
{

	/**
	 * @var string
	 */
	private $clientKey;

	/**
	 * @var string
	 */
	private $clientSecret;

	/**
	 * @var string
	 */
	private $baseUrl;

	public function __construct(
		string $clientKey,
		string $clientSecret,
		string $baseUrl = 'http://localhost:8080'
	) {
		$this->clientKey = $clientKey;
		$this->clientSecret = $clientSecret;

		$this->baseUrl = $baseUrl;
	}

	public function getClientKey(): string
	{
		return $this->clientKey;
	}

	public function getClientSecret(): string
	{
		return $this->clientSecret;
	}

	public function getBaseUrl(): string
	{
		return $this->baseUrl;
	}
}
