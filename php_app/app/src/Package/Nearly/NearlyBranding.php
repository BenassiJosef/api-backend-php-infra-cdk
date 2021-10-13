<?php

namespace App\Package\Nearly;

use App\Controllers\Integrations\S3\S3;
use App\Models\Locations\Branding\LocationBranding;

class NearlyBranding
{
    /**
     * @var S3 $s3
     */
    protected $s3;

    /**
     * NearlyBranding constructor.
     */
    public function __construct()
    {
        $this->s3          = new S3('nearly.online', 'eu-west-1');
    }

    public function parseNearlyBranding(LocationBranding $branding, string $serial): LocationBranding
    {

        $base = 'static/media/branding/' . $serial . '/';
		return $branding;
        if ($branding->getBackgroundImage()) {
            //  $branding->setBackgroundImage($this->handleFile($branding->getBackgroundImage(), $base));
        }
        if ($branding->getHeaderImage()) {
            //  $branding->setHeaderImage($this->handleFile($branding->getHeaderImage(), $base));
        }


        if (!is_null($branding->getCustomCSS()) && !empty($branding->getCustomCSS())) {
            if (!$this->s3->doesObjectExist($base . $serial . '/css/custom.css')) {
                $upload = $this->s3->upload(
                    $base . $serial . '/css/custom.css',
                    'string',
                    $branding->getCustomCSS(),
                    'public-read',
                    [
                        'CacheControl' => 'max-age=31536000',
                        'ContentType'  => 'text/css'
                    ]
                );

               // $branding->setCustomCSS(file_get_contents($upload));
            } else {
/*
                $branding->setCustomCSS(file_get_contents($this->s3->AbsoluteBucket() . $base .
                    $serial . '/css/custom.css'));
*/
            }
        }

        return $branding;
    }

    public function handleFile(string $file, string $base)
    {

        $filename  = $base . $this->filenameFromUrl($file);

        if (!is_null($file) && !empty($file)) {
            try {
                //Does this file exist in the nearly bucket, if not, copy it over. Get the URL of file.
                if (!$this->s3->doesObjectExist($filename)) {

                    $upload = $this->s3->upload(
                        $filename,
                        'string',
                        $file,
                        'public-read',
                        [
                            'CacheControl' => 'max-age=31536000'
                        ]
                    );

                    return $upload;
                } else {
                    return $this->s3->AbsoluteBucket() . $filename;
                }
            } catch (\Guzzle\Common\Exception\InvalidArgumentException $e) {
                return '';
            }
        }
    }

    public function filenameFromUrl(string $uri)
    {
        $parts = explode('/', $uri);
        return array_pop($parts);
    }
}
