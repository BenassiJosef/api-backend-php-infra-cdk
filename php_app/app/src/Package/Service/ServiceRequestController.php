<?php

namespace App\Package\Service;

use Slim\Http\Request;
use Slim\Http\Response;

class ServiceRequestController
{



	public function get(Request $request, Response $response): Response
	{

		$service = new ServiceRequest();

		return $response->withJson($service->get('03126055-361f-11ea-9472-06a4d6597160/marketing/domains'));
	}
}
