<?php
/**
 * Created by jamieaitken on 15/05/2018 at 12:17
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile\NearlyProfileCSVComponents;

abstract class NearlyProfileCSVComponent
{

    protected $headers;
    protected $contents;

    public abstract function create();

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function setContents(array $contents)
    {
        $this->contents = $contents;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getContents()
    {
        return $this->contents;
    }

    protected function formatDetails(string $property, $value)
    {
        if (!is_null($value) && !empty($value)) {
            if ($property === 'Verified' || $property === 'Postcode Valid') {
                if ($value === 1) {
                    $value = 'Yes';
                } else {
                    $value = 'No';
                }
            } elseif ($property === 'Gender') {
                switch ($value) {
                    case 'm':
                        $value = 'Male';
                        break;
                    case 'f':
                        $value = 'Female';
                        break;
                    case 'o':
                        $value = 'Other';
                        break;
                }
            }

            return $value;
        }

        return 'N/A';
    }
}