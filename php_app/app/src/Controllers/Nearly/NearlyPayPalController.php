<?php

/**
 * Created by jamieaitken on 08/03/2018 at 09:37
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly;

use App\Controllers\Integrations\Mixpanel\_Mixpanel;
use App\Controllers\Locations\Pricing\_LocationPlanController;
use App\Controllers\Payments\_PaymentsController;
use App\Models\Locations\LocationSettings;
use App\Package\Nearly\NearlyAuthentication;
use App\Package\Nearly\NearlyInput;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Mixpanel;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;

class NearlyPayPalController
{
	/**
	 * @var NearlyAuthentication $nearlyAuthentication
	 */
	private $nearlyAuthentication;

	protected $em;
	protected $API_ENDPOINT = '';
	protected $apiUserName  = '';
	protected $apiPassword  = '';
	protected $apiSignature = '';
	protected $PAYPAL_URL;
	private   $token;
	protected $nearlyCache;
	public    $PROXY_HOST   = '127.0.0.1';
	public    $PROXY_PORT   = '808';
	public    $SandboxFlag  = false;
	public    $sBNCode      = "PP-ECWizard";
	public    $USE_PROXY    = false;
	public    $version      = "64";
	public    $hostname     = 'https://nearly.online/api/';
	public    $nearly       = 'https://nearly.online/';

	protected $mp;
	private   $logger;

	public function __construct(Logger $logger, EntityManager $em, CacheEngine $nearlyCache, _Mixpanel $mixpanel, NearlyAuthentication $nearlyAuthentication)
	{
		$this->logger               = $logger;
		$this->em                   = $em;
		$this->nearlyCache          = $nearlyCache;
		$this->mp                   = $mixpanel;
		$this->nearlyAuthentication = $nearlyAuthentication;
	}

	public function createRoute(Request $request, Response $response)
	{
		$body = $request->getParsedBody();

		$send = $this->create($body);

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function redirectRoute(Request $request, Response $response)
	{
		$params = $request->getQueryParams();

		$send = $this->redirect($params);

		$this->em->clear();

		if ($send['status'] !== 302) {

			return $response->withJson($send, $send['status']);
		}

		return $response->withStatus(302)
			->withHeader('Location', $this->nearly . 'paypal-confirm?' . http_build_query($send['message']));
	}

	public function confirmRoute(Request $request, Response $response)
	{

		$send = $this->confirm($request);

		$this->em->clear();

		return $response->withJson($send, $send['status']);
	}

	public function create(array $body)
	{
		$profileId = false;
		if (!isset($body['serial'])) {
			return Http::status(409, 'SERIAL_MISSING');
		}

		if (!isset($body['paymentKey'])) {
			return Http::status(409, 'PAYMENTKEY_MISSING');
		}

		if (isset($body['meta']['id'])) {
			$profileId = $body['meta']['id'];
		}

		$serial               = $body['serial'];
		$paymentKey           = $body['paymentKey'];
		$settingsController   = new _NearlyController($this->logger, $this->em);
		$paypalAccountRequest = $settingsController->paypalSettings($serial);

		if ($paypalAccountRequest['status'] !== 200) {
			return $paypalAccountRequest;
		}

		$newPlanController = new _LocationPlanController($this->em);
		$plan              = $newPlanController->getPlanFromId($paymentKey);

		$getAliasAndCurrency = $this->em->createQueryBuilder()
			->select('u.alias, u.currency')
			->from(LocationSettings::class, 'u')
			->where('u.serial = :s')
			->setParameter('s', $serial)
			->getQuery()
			->getArrayResult()[0];

		$desc = $plan['name'] . ' Plan ' . $getAliasAndCurrency['alias'];

		$price = (float)$plan['cost'] / 100;

		$paymentType = 'Sale';
		$cancelURL   = 'http://302.black';

		$this->initialise(
			$paypalAccountRequest['message']['username'],
			$paypalAccountRequest['message']['password'],
			$paypalAccountRequest['message']['signature']
		);

		$returnUrl = $this->hostname . 'nearly/paypal';

		$request = $this->callShortcutExpressCheckout(
			$price,
			$getAliasAndCurrency['currency'],
			$paymentType,
			$returnUrl,
			$cancelURL,
			$desc
		);

		$ack = strtoupper($request['ACK']);

		if ($ack !== 'SUCCESS' && $ack !== 'SUCCESSWITHWARNING') {

			$this->mp->track(
				'PAYPAL_FAILED_CREATE_SHORTCUT_EXPRESS_CHECKOUT',
				[
					'input'  => $body,
					'output' => $request
				]
			);

			return Http::status(
				400,
				[
					'API_FAILED' => [
						'REASON' => $request["L_LONGMESSAGE0"],
						'CODE'   => $request['L_SEVERITYCODE0']
					]
				]
			);
		}

		$cacheData = [
			'serial'     => $serial,
			'paymentKey' => $paymentKey,
			'meta'       => $body['meta'],
			'profileId'  => $profileId
		];

		$this->nearlyCache->save('paypalTransactions:' . $request['TOKEN'], $cacheData);

		$url = $this->RedirectToPayPal();

		return Http::status(200, $url);
	}

	public function redirect(array $params)
	{
		if (!isset($params['token'])) {
			$this->mp->track(
				'PAYPAL_REDIRECT_WITH_NO_TOKEN',
				[
					'input' => $params
				]
			);

			return Http::status(400, 'NO_TOKEN_PASSED');
		}

		$token = $params['token'];

		$fetch = $this->nearlyCache->fetch('paypalTransactions:' . $token);

		if (is_bool($fetch)) {
			$this->mp->track(
				'PAYPAL_TOKEN_DOES_NOT_EXIST_IN_CACHE',
				[
					'input' => $params
				]
			);

			return Http::status(400, 'TOKEN_DOES_NOT_EXIST_IN_CACHE');
		}

		$settingsController   = new _NearlyController($this->logger, $this->em);
		$paypalAccountRequest = $settingsController->paypalSettings($fetch['serial']);
		if ($paypalAccountRequest['status'] !== 200) {
			return $paypalAccountRequest;
		}

		$this->initialise(
			$paypalAccountRequest['message']['username'],
			$paypalAccountRequest['message']['password'],
			$paypalAccountRequest['message']['signature']
		);

		$resArray = $this->getShippingDetails($token);

		$ack = strtoupper($resArray['ACK']);

		if ($ack !== 'SUCCESS' && $ack !== 'SUCCESSWITHWARNING') {
			$this->mp->track(
				'PAYPAL_ERROR_FAILED_TO_GET_DETAILS',
				[
					'input'  => $params,
					'output' => $resArray
				]
			);

			return Http::status(400, 'FAILED_TO_GET_SHIPPING_DETAILS');
		}

		$fetch['paypal'] = $resArray;

		$this->nearlyCache->save('paypalTransactions:' . $token, $fetch);

		return Http::status(302, $resArray);
	}

	public function confirm(Request $request)
	{
		$params = $request->getParsedBody();
		$meta = $request->getParsedBodyParam('meta', null);
		$token = $request->getParsedBodyParam('token', null);
		if (is_null($token)) {
			$this->mp->track(
				'PAYPAL_ERROR_NO_TOKEN_PASSED',
				[
					'input' => $token
				]
			);

			return Http::status(400, 'NO_TOKEN');
		}



		$fetch = $this->nearlyCache->fetch('paypalTransactions:' . $token);

		$settingsController   = new _NearlyController($this->logger, $this->em);
		$paypalAccountRequest = $settingsController->paypalSettings($fetch['serial']);
		if ($paypalAccountRequest['status'] !== 200) {
			return $paypalAccountRequest;
		}

		$this->initialise(
			$paypalAccountRequest['message']['username'],
			$paypalAccountRequest['message']['password'],
			$paypalAccountRequest['message']['signature']
		);

		$getAliasAndCurrency = $this->em->createQueryBuilder()
			->select('u.alias, u.currency')
			->from(LocationSettings::class, 'u')
			->where('u.serial = :s')
			->setParameter('s', $fetch['serial'])
			->getQuery()
			->getArrayResult()[0];

		$resArray = $this->confirmPayment((object)$fetch['paypal'], $getAliasAndCurrency['currency']);

		$ack = strtoupper($resArray['ACK']);

		if ($ack !== 'SUCCESS' && $ack !== 'SUCCESSWITHWARNING') {

			$this->mp->track(
				'PAYPAL_ERROR_FAILED_TO_CONFIRM',
				[
					'input'  => $params,
					'output' => $resArray
				]
			);

			return Http::status(400, $fetch['meta']['link']);
		}
		/**
		 * @var NearlyInput $input
		 */
		$nearlyInput    = new NearlyInput();
		$input = $nearlyInput::createFromArray($meta);
		$input->setDataOptIn(true);

		$payment  = new _PaymentsController($this->logger, $this->em, new _Mixpanel());
		$customer = [
			'email'    => $fetch['paypal']['EMAIL'],
			'first'    => $fetch['paypal']['FIRSTNAME'],
			'last'     => $fetch['paypal']['LASTNAME'],
			'postcode' => $fetch['paypal']['SHIPTOZIP']
		];

		if ($fetch['profileId'] !== false) {
			$input->setProfileId($fetch['profileId']);
			$customer['id'] = $fetch['profileId'];
		}

		$chargeArray = [
			'method'         => 'paypal',
			'customer'       => $customer,
			'serial'         => $fetch['serial'],
			'paymentKey'     => $fetch['paymentKey'],
			'transaction_id' => $resArray['PAYMENTINFO_0_TRANSACTIONID']
		];

		$charge = $payment->createPayment($chargeArray);


		if ($charge['status'] !== 200) {
			$this->mp->track(
				'PAYPAL_ERROR_FAILED_TO_CREATE_CHARGE',
				[
					'input' => $chargeArray
				]
			);

			return $charge;
		}
		$input->setProfileId($charge['message']['customer']['id']);

		$session = $this->nearlyAuthentication;

		$payload = $session->createSession($input);
		if (!is_null($payload)) {
			return Http::status(200, $payload->jsonSerialize());
		}
		return Http::status(403, 'SOMETHING_WENT_WRONG');
	}


	private function initialise(string $username, string $password, string $signature)
	{
		$this->apiUserName  = $username;
		$this->apiPassword  = $password;
		$this->apiSignature = $signature;
		$this->API_ENDPOINT = "https://api-3t.paypal.com/nvp";
		$this->PAYPAL_URL   = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
	}

	/**
	 * Beginning of PayPal Functions
	 */

	/**
	 * @param float $price
	 * @param string $currency
	 * @param string $paymentType
	 * @param string $returnUrl
	 * @param string $cancelUrl
	 * @param string $description
	 * @return array
	 */

	private function callShortcutExpressCheckout(
		float $price,
		string $currency,
		string $paymentType,
		string $returnUrl,
		string $cancelUrl,
		string $description
	) {
		//------------------------------------------------------------------------------------------------------------------------------------
		// Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation

		$nvpstr = "&PAYMENTREQUEST_0_AMT=" . $price;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_PAYMENTACTION=" . $paymentType;
		$nvpstr = $nvpstr . "&RETURNURL=" . $returnUrl;
		$nvpstr = $nvpstr . "&CANCELURL=" . $cancelUrl;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_CURRENCYCODE=" . $currency;
		$nvpstr = $nvpstr . "&PAYMENTREQUEST_0_DESC=" . $description;


		$_SESSION["currencyCodeType"] = $currency;
		$_SESSION["PaymentType"]      = $paymentType;

		//'---------------------------------------------------------------------------------------------------------------
		//' Make the API call to PayPal
		//' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.
		//' If an error occured, show the resulting errors
		//'---------------------------------------------------------------------------------------------------------------
		$resArray = $this->hash_call("SetExpressCheckout", $nvpstr);

		$ack = strtoupper($resArray["ACK"]);
		if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
			$token       = urldecode($resArray["TOKEN"]);
			$this->token = $token;
		}

		return $resArray;
	}

	/**
	 * @param string $methodName
	 * @param string $nvpStr
	 * @return array
	 */

	private function hash_call(string $methodName, string $nvpStr)
	{
		//declaring of global variables
		global $gv_ApiErrorURL;

		//setting the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->API_ENDPOINT);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		//turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);

		//if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
		//Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php
		if ($this->USE_PROXY) {
			curl_setopt($ch, CURLOPT_PROXY, $this->PROXY_HOST . ":" . $this->PROXY_PORT);
		}

		//NVPRequest for submitting to server
		$nvpreq = "METHOD=" . urlencode($methodName) .
			"&VERSION=" . urlencode($this->version) .
			"&PWD=" . urlencode($this->apiPassword) .
			"&USER=" . urlencode($this->apiUserName) .
			"&SIGNATURE=" . urlencode($this->apiSignature) . $nvpStr .
			"&BUTTONSOURCE=" . urlencode($this->sBNCode);


		//setting the nvpreq as POST FIELD to curl
		curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

		//getting response from server
		$response = curl_exec($ch);

		//convrting NVPResponse to an Associative Array
		$nvpResArray = $this->deformatNVP($response);
		$nvpReqArray = $this->deformatNVP($nvpreq);

		if (curl_errno($ch)) {
			// moving to display page to display curl errors


			//Execute the Error handling module to display errors.
		} else {
			//closing the curl
			curl_close($ch);
		}

		return $nvpResArray;
	}

	/*'----------------------------------------------------------------------------------
     * This function will take NVPString and convert it to an Associative Array and it will decode the response.
      * It is usefull to search for a particular key and displaying arrays.
      * @nvpstr is NVPString.
      * @nvpArray is Associative Array.
       ----------------------------------------------------------------------------------
      */
	public function deformatNVP(string $nvpstr)
	{
		$intial   = 0;
		$nvpArray = [];

		while (strlen($nvpstr)) {
			//postion of Key
			$keypos = strpos($nvpstr, '=');
			//position of value
			$valuepos = strpos($nvpstr, '&') ? strpos($nvpstr, '&') : strlen($nvpstr);

			/*getting the Key and Value values and storing in a Associative Array*/
			$keyval = substr($nvpstr, $intial, $keypos);
			$valval = substr($nvpstr, $keypos + 1, $valuepos - $keypos - 1);
			//decoding the respose
			$nvpArray[urldecode($keyval)] = urldecode($valval);
			$nvpstr                       = substr($nvpstr, $valuepos + 1, strlen($nvpstr));
		}

		return $nvpArray;
	}

	/*'----------------------------------------------------------------------------------
     Purpose: Redirects to PayPal.com site.
     Inputs:  NVP string.
     Returns:
    ----------------------------------------------------------------------------------
    */
	public function RedirectToPayPal()
	{

		// Redirect to paypal.com here
		$payPalURL = $this->PAYPAL_URL . $this->token;

		return $payPalURL;
	}

	/*
    '-------------------------------------------------------------------------------------------
    ' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
    '
    ' Inputs:
    '		None
    ' Returns:
    '		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
    '-------------------------------------------------------------------------------------------
    */
	public function getShippingDetails($token)
	{
		//'--------------------------------------------------------------
		//' At this point, the buyer has completed authorizing the payment
		//' at PayPal.  The function will call PayPal to obtain the details
		//' of the authorization, incuding any shipping information of the
		//' buyer.  Remember, the authorization is not a completed transaction
		//' at this state - the buyer still needs an additional step to finalize
		//' the transaction
		//'--------------------------------------------------------------

		//'---------------------------------------------------------------------------
		//' Build a second API request to PayPal, using the token as the
		//'  ID to get the details on the payment authorization
		//'---------------------------------------------------------------------------
		$nvpstr = "&TOKEN=" . $token;

		//'---------------------------------------------------------------------------
		//' Make the API call and store the results in an array.
		//'	If the call was a success, show the authorization details, and provide
		//' 	an action to complete the payment.
		//'	If failed, show the error
		//'---------------------------------------------------------------------------
		$resArray = $this->hash_call("GetExpressCheckoutDetails", $nvpstr);
		$ack      = strtoupper($resArray["ACK"]);
		if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
			$_SESSION['payer_id'] = $resArray['PAYERID'];
		}

		return $resArray;
	}

	/*
    '-------------------------------------------------------------------------------------------------------------------------------------------
    ' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
    '
    ' Inputs:
    '		sBNCode:	The BN code used by PayPal to track the transactions from a given shopping cart.
    ' Returns:
    '		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
    '--------------------------------------------------------------------------------------------------------------------------------------------
    */
	public function confirmPayment($paypalObj, $currency)
	{
		/* Gather the information to make the final call to
           finalize the PayPal payment.  The variable nvpstr
           holds the name value pairs
           */


		//Format the other parameters that were stored in the session from the previous calls
		$token            = urlencode($paypalObj->TOKEN);
		$paymentType      = urlencode('Sale');
		$currencyCodeType = urlencode($currency);
		$payerID          = urlencode($paypalObj->PAYERID);
		$serverName       = urlencode($_SERVER['SERVER_NAME']);

		$nvpstr = '&TOKEN=' . $token .
			'&PAYERID=' . $payerID .
			'&PAYMENTREQUEST_0_PAYMENTACTION=' . $paymentType .
			'&PAYMENTREQUEST_0_AMT=' . $paypalObj->PAYMENTREQUEST_0_AMT;

		$nvpstr .= '&PAYMENTREQUEST_0_CURRENCYCODE=' . $currencyCodeType .
			'&IPADDRESS=' . $serverName;

		/* Make the call to PayPal to finalize payment
           If an error occured, show the resulting errors
           */
		$resArray = $this->hash_call("DoExpressCheckoutPayment", $nvpstr);

		/* Display the API response back to the browser.
           If the response from PayPal was a success, display the response parameters'
           If the response was an error, display the errors received using APIError.php.
           */
		$ack = strtoupper($resArray["ACK"]);

		return $resArray;
	}
}
