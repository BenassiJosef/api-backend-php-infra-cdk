<?php
/**
 * Created by jamieaitken on 23/07/2018 at 12:03
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\CloudFront;


use App\Utils\Strings;
use Aws\CloudFront\CloudFrontClient;

class CloudFront
{
    protected $cloudFront;

    public function __construct()
    {

        $this->cloudFront = new CloudFrontClient([
            'version'     => 'latest',
            'region'      => 'eu-west-1',
            'credentials' => [
                'key'    => getenv('AWS_ACCESS_KEY_ID'),
                'secret' => getenv('AWS_SECRET_ACCESS_KEY')
            ]
        ]);
    }

    public function invalidate(string $serial)
    {
        $this->cloudFront->createInvalidation([
            'DistributionId'    => 'E5XJUJDXGMOMH',
            'InvalidationBatch' => [
                'CallerReference' => Strings::idGenerator('api'),
                'Paths'           => [
                    'Items'    => ['/static/media/branding/' . $serial . '/css/custom.css'],
                    'Quantity' => 1
                ]
            ]
        ]);
    }
}