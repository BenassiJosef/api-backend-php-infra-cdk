<?php
/**
 * Created by jamieaitken on 03/05/2018 at 16:59
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\NearlyProfile\NearlyProfilePDFComponents;


abstract class NearlyProfilePDFComponent
{
    protected $header;
    protected $contents;

    public abstract function create();

    public function setHeader(string $header)
    {
        $this->header = $header;
    }

    public function setContents(array $contents)
    {
        $this->contents = $contents;
    }

    public function getHeader()
    {
        return '<h1>' . $this->header . '</h1>';
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

            return "<tr><td>" . $property . ":</td><td>" . $value . "</td></tr>";
        }

        return "<tr><td>" . $property . ":</td><td>N/A</td></tr>";
    }
}