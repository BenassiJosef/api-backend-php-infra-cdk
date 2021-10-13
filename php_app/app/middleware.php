<?php

// Application middleware

$app->add(function ($request, $response, $next) {

	$responsen = $response->withHeader('Content-Type', 'application/json; charset=utf-8')
		->withHeader('X-Powered-By', $this->settings['PoweredBy']);


	/* $APIRateLimit = new App\Utils\APIRateLimiter($this);
	$mustbethrottled = $APIRateLimit();
	
	if ($mustbethrottled == false) {
        $responsen = $next($request, $responsen);
	} else {
        $responsen = $responsen ->withStatus(429)
                                ->withHeader('RateLimit-Limit', $this->settings['api_rate_limiter']['requests']);
	}
*/
	return $next($request, $responsen);
});
/*
$app->add(function ($request, $response, $next) {
	try {
		\Sentry\init(['dsn' => 'https://08abd7305df346b083417a9d5141c304@o142237.ingest.sentry.io/5949781']);
		return $next($request, $response);
	} catch (\Throwable $e) {
		\Sentry\captureException($e);
		throw $e;
	}
});
*/

$app->add(function ($request, $response, $next) {
	/**
	 * @var \Slim\Http\Request $request
	 */
	try {
		return $next($request, $response);
	} catch (\Throwable $e) {
		if (extension_loaded('newrelic')) {
			foreach ($request->getHeaders() as $name => $value) {
				newrelic_add_custom_parameter("Header $name", $value);
			}
			newrelic_notice_error($e);
		}
		throw $e;
	}
});
