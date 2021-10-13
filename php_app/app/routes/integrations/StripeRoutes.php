<?php


use App\Controllers\Integrations\Stripe\_StripeController;

$this->group(
    '/stripe', function () {
    $this->get('/authorize', _StripeController::class . ':authorize');
    $this->get('/deauthorize', _StripeController::class . ':deauthorizeAccountRoute');
    $this->get('', _StripeController::class . ':get');
}
);