<?php
/**
 * Created by jamieaitken on 03/05/2018 at 09:58
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Utils\Exporters;

use App\Controllers\Integrations\Uploads\_UploadStorageController;
use App\Utils\Http;
use Aws\S3\S3Client;
use Psr\Http\Message\ResponseInterface;

class Download
{

    public function generateDownload(
        string $filename,
        string $contents,
        string $extension,
        ResponseInterface $response,
        string $profileId
    ) {

        $s3 = S3Client::factory([
            'version'   => 'latest',
            'region'    => 'eu-west-2',
            'signature' => 'v4'
        ]);

        $upload = $s3->putObject([
            'Body'   => $contents,
            'Key'    => 'gdprExports' . '/' . $profileId . '/' . $filename . '.' . $extension,
            'Bucket' => 'blackbx',
            'ACL'    => 'public-read'
        ]);


        return $response->withJson(Http::status(200, $upload->get('ObjectURL')), 200);
    }
}