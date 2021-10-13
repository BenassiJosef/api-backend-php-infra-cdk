<?php
/**
 * Created by jamieaitken on 19/03/2018 at 13:03
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\Stripe\_StripeCardsController;
use App\Controllers\Integrations\Stripe\_StripeChargeController;
use App\Controllers\Integrations\Stripe\_StripeCustomerController;

$app->group(
    '/stripe', function () {

    $this->get('/callback', App\Controllers\Integrations\Stripe\_StripeController::class . ':callback');
    $this->group(
        '/customer', function () {
        $this->post('', _StripeCustomerController::class . ':postCustomerRoute');
        $this->delete(
            '/{id}',
            _StripeCustomerController::class . ':deleteCustomerRoute'
        );
        $this->put(
            '/{id}',
            _StripeCustomerController::class . ':updateCustomerRoute'
        );
        $this->get(
            '/{id}',
            _StripeCustomerController::class . ':retrieveCustomerRoute'
        );
    }
    );
    $this->group(
        '/card', function () {
        $this->post('', _StripeCardsController::class . ':addCard');
        $this->get('/{customerId}', _StripeCardsController::class . ':getCards');
        $this->delete(
            '/{cardId}/{customerId}',
            _StripeCardsController::class . ':deleteCard'
        );

    }
    );
    $this->group(
        '/charge', function () {
        $this->post('', _StripeChargeController::class . ':createChargeRoute');
        $this->put(
            '/{customerId}/{chargeId}',
            _StripeChargeController::class . ':updateChargeRoute'
        );
        $this->get(
            '/{customerId}',
            _StripeChargeController::class . ':retrieveAllChargesRoute'
        );
        $this->get(
            '/{customerId}/{chargeId}',
            _StripeChargeController::class . ':retrieveChargeRoute'
        );
    }
    );
}
);