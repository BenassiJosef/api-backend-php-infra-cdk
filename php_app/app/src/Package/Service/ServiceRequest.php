<?php

namespace App\Package\Service;

use Curl\Curl;
use DateTime;
use Error;

class ServiceRequest
{

	const API_TOKEN_ENDPOINT = '/oauth/token';
	/**
	 * @var string $urlKey
	 */
	private $urlKey = 'SERVICE_URL';

	/**
	 * @var string $serviceUrl
	 */
	private $serviceUrl = '';

	/**
	 * @var string $authUrl
	 */
	private $authUrl = '';

	/**

	 * @var Curl
	 */
	private $client;

	/**
	 * @var ServiceCredentials $credentials
	 */
	private $credentials;


	/**
	 *
	 * @var ServiceAccessToken 
	 */
	private $accessToken;

	/**
	 * Service request constructor.

	 */
	public function __construct()
	{
		$credentials = new ServiceCredentials(
			getenv('SERVICE_OAUTH_CLIENT_ID'),
			getenv('SERVICE_OAUTH_CLIENT_SECRET'),
			'https://' . getenv('API_URL') . '/'
		);

		$this->authUrl = $credentials->getBaseUrl();
		$this->serviceUrl = 'https://' . getenv($this->urlKey) . '/';

		$this->credentials = $credentials;
	}

	private function hasAccessToken(): bool
	{
		return $this->accessToken instanceof ServiceAccessToken && $this->accessToken->getExpires() > new DateTime();
	}

	private function getAccessToken(): ServiceAccessToken
	{
		return $this->accessToken;
	}

	private function getCredentials(): ServiceCredentials
	{
		return $this->credentials;
	}

	private function refreshAccessToken()
	{
		$authCurl = new Curl($this->authUrl);

		$authCurl->setHeader('Content-Type', 'application/json');
		$payload = json_encode([
			'grant_type' => 'client_credentials',
			'client_id' => $this->getCredentials()->getClientKey(),
			'client_secret' => $this->getCredentials()->getClientSecret(),
			'scope' => 'ALL:ALL',
		]);
		$authCurl->post(
			$this->authUrl . self::API_TOKEN_ENDPOINT,
			$payload
		);
		if ($authCurl->error) {
			throw new ServiceException($authCurl->errorMessage, $authCurl->httpStatusCode);
		} else {
			$json = json_encode($authCurl->response);
			$this->accessToken = new ServiceAccessToken($json);
		}
	}

	private function getClient(): Curl
	{
		$this->validateToken();
		$client = new Curl();
		$client->setHeader('Content-Type', 'application/json');
		$header = $this->getAccessToken()->getType() . ' ' . $this->getAccessToken()->getToken();

		$client->setHeader('Authorization', $header);
		return $client;
	}

	private function validateToken()
	{
		if (!$this->hasAccessToken()) {
			$this->refreshAccessToken();
		}
	}

	private function handleResponse(Curl $curlClient)
	{
		if ($curlClient->error) {
			throw new ServiceException($curlClient->errorMessage, $curlClient->httpStatusCode);
		} else {
			$json = $curlClient->response;
			return $json;
		}
	}

	public function get(string $path = '')
	{
		$client = $this->getClient();
		$client->get($this->serviceUrl . $path);
		return $this->handleResponse($client);
	}

	public function post(string $path = '', array $data)
	{
		$client = $this->getClient();
		$client->post($this->serviceUrl . $path, $data);
		return $this->handleResponse($client);
	}
}
