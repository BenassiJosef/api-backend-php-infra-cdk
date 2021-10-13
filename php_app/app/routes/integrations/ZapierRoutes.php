<?php
/**
 * Created by patrickclover on 03/08/2018 at 15:08
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

use App\Controllers\Integrations\Hooks\_HooksController;

$this->group(
    '/zapier', function () {
    $this->get('', _HooksController::class . ':getAllRoute');
    $this->put('', _HooksController::class . ':sendRoute');
}
);