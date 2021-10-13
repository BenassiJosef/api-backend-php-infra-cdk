<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 14/02/2017
 * Time: 20:36
 */

namespace App\Utils;

class Arrays
{

    public static function toXML($data, $key)
    {
        $xml_data = new \SimpleXMLElement('<?xml version="1.0"?><'.$key.'></'.$key.'>');

        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item' . $key; //dealing with <0/>..<n/> issues
            }
            if (is_array($value)) {
                $subnode = $xml_data->addChild($key);
                self::toXML($value, $subnode);
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
        return $xml_data->asXML();
    }

    /**
     * Encode array to utf8 recursively
     * @param $dat
     * @return array|string
     */
    public static function array_utf8_encode($dat)
    {
        if (is_string($dat)) {
            return utf8_encode($dat);
        }
        if (!is_array($dat)) {
            return $dat;
        }
        $ret = array();
        foreach ($dat as $i => $d) {
            $ret[$i] = self::array_utf8_encode($d);
        }
        return $ret;
    }
}
