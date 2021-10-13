<?php

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 15/02/2017
 * Time: 16:03
 */

namespace App\Controllers\Integrations\Uploads;

use App\Utils\Strings;
use Aws\S3\S3Client;

class _UploadsController
{

    public $bucket = 'blackbx';
    protected $s3;

    public function __construct(string $region = 'eu-west-2')
    {
        $this->s3 = S3Client::factory([
            'version'   => 'latest',
            'region'    => $region,
            'signature' => 'v4'
        ]);
    }

    public function resizeImageFile($file, string $type)
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $imageAttributes = getimagesize($file['tmp_name']);

        $minimumWidth = null;
        if ($type === 'header') {
            $minimumWidth = 600;
        } elseif ($type === 'background') {
            $minimumWidth = 1500;
        }

        $imageAspectRatio = $imageAttributes[1] / $imageAttributes[0];

        $newHeight = $minimumWidth * $imageAspectRatio;

        $newImageSizeTrueColour = imagecreatetruecolor($minimumWidth, $newHeight);

        $type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($type === 'png') {
            imagealphablending($newImageSizeTrueColour, false);
            imagesavealpha($newImageSizeTrueColour, true);
            $image = imagecreatefrompng($file['tmp_name']);
            imagealphablending($image, true);
        } else {
            $image = imagecreatefromjpeg($file['tmp_name']);
        }

        imagecopyresampled(
            $newImageSizeTrueColour,
            $image,
            0,
            0,
            0,
            0,
            $minimumWidth,
            $newHeight,
            $imageAttributes[0],
            $imageAttributes[1]
        );

        $fileResize = false;
        if ($type === 'png') {
            $fileResize = imagepng($newImageSizeTrueColour, $file['tmp_name'], 9);
        } elseif ($type === 'jpg' || $type === 'jpeg') {
            $fileResize = imagejpeg($newImageSizeTrueColour, $file['tmp_name'], 100);
        }

        if ($fileResize !== true) {
            return null;
        }

        return $file;
    }


    public function storeString(
        string $string = '',
        string $extension = '',
        string $dir = '',
        string $fileName = null
    ) {
        if (!$fileName) {
            $fileName = Strings::idGenerator('fle');
        }

        $file = $this->s3->putObject([
            'Body'   => $string,
            'Key'    => $dir . '/' . $fileName . '.' . $extension,
            'Bucket' => $this->bucket
        ]);

        return $file->get('ObjectURL');
    }

    public function loadFile($file)
    {
        return file_get_contents($file);
    }

    public function getFile($key)
    {
        $file = $this->s3->getObject([
            'Bucket' => $this->bucket,
            'Key'    => $key
        ]);

        return $file['Body'];
    }

    public function setBucket(string $bucket)
    {
        $this->bucket = $bucket;
        return $this;
    }

    public function checkDir(string $path)
    {
        return $this->s3->doesObjectExist($this->bucket, $path);
    }
}
