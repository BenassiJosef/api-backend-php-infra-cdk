<?php

namespace App\Filters;

class TwigFilters extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('bytes', [$this, 'bytesFilter']),
            new \Twig_SimpleFilter('number', [$this, 'numberFilter']),
            new \Twig_SimpleFilter('sec2Time', [$this, 'sec2Time'])
        ];
    }

    public function bytesFilter($bytes, $decimals = 0, $decPoint = '.', $thousandsSep = ',')
    {
        //$price = number_format($bytes, $decimals, $decPoint, $thousandsSep);

        $bytes = (int)$bytes;
        if ($bytes === 0 || !$bytes || is_null($bytes)) {
            return 0;
        }

        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    public function numberFilter($bytes, $decimals = 0, $decPoint = '.', $thousandsSep = ',')
    {
        return number_format($bytes, $decimals, $decPoint, $thousandsSep);
    }

    public function sec2Time($secs)
    {
        if (is_numeric($secs)) {
            $value = [
                "y"   => 0,
                "d"    => 0,
                "h"   => 0,
                "m" => 0,
                "s" => 0,
            ];
            if ($secs >= 31556926) {
                $value["y"] = floor($secs / 31556926);
                $secs           = ($secs % 31556926);
            }
            if ($secs >= 86400) {
                $value["d"] = floor($secs / 86400);
                $secs          = ($secs % 86400);
            }
            if ($secs >= 3600) {
                $value["h"] = floor($secs / 3600);
                $secs           = ($secs % 3600);
            }
            if ($secs >= 60) {
                $value["m"] = floor($secs / 60);
                $secs             = ($secs % 60);
            }
            $value["s"] = floor($secs);

            $cleanArr = [];
            foreach ($value as $key => $v) {
                if ($v !== 0) {
                    $cleanArr[] = $v.$key;
                }
            }

            return implode(':', $cleanArr);

        } else {
            return 0 . 's';
        }
    }
}