<?php
/**
 * Created by jamieaitken on 23/02/2018 at 22:09
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\S3;

use Aws\S3\S3Client;

class S3
{
    protected $s3;
    private $bucket = 'blackbx';
    private $region = 'eu-west-2';

    public function __construct(string $bucket, string $region)
    {
        $credentials = [
            'version'   => 'latest',
            'signature' => 'v4'
        ];

        if (!empty($region)) {
            $this->region = $region;
        }

        if (!empty($bucket)) {
            $this->bucket = $bucket;
        }

        $credentials['region'] = $this->region;

        $this->s3 = S3Client::factory($credentials);
    }

    public function upload(string $key, string $methodToObtainFile, string $filePath, string $acl, array $options)
    {
        switch ($methodToObtainFile) {
            case 'string':
                $file = file_get_contents($filePath);
                break;
            case 'resource':
                $file = fopen($filePath, 'r');
                break;
        }
        if (empty($options)) {
            $upload = $this->s3->upload(
                $this->bucket,
                $key,
                $file,
                $acl
            );
        } else {
            $upload = $this->s3->upload(
                $this->bucket,
                $key,
                $file,
                $acl,
                [
                    'params' => $options
                ]
            );
        }

        return $upload->get('ObjectURL');
    }

    public function doesObjectExist(string $file)
    {
        if ($this->s3->doesObjectExist($this->bucket, $file)) {
            return true;
        }

        return false;
    }

    public function removeFile(string $file)
    {
        return $this->s3->deleteObject([
            'Bucket' => $this->bucket,
            'Key'    => $file
        ]);
    }

    public function Client()
    {
        return $this->s3;
    }

    public function Bucket()
    {
        return $this->bucket;
    }

    public function Region()
    {
        return $this->region;
    }

    public function AbsoluteBucket()
    {
        return 'https://s3-' . $this->region . '.amazonaws.com/' . $this->bucket . '/';
    }
}