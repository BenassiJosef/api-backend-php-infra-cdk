<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 01/02/2017
 * Time: 22:56
 */

namespace App\Utils;

use App\Models\Notifications\FeatureRequest;
use App\Models\Notifications\FeatureRequestVote;

class Strings
{

    public static function random($length = 6)
    {

        $str        = "";
        $characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
        $max        = count($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $max);
            $str  .= $characters[$rand];
        }

        return $str;
    }

    public static function idGenerator($idPrefix)
    {
        $newDateTime = new \DateTime();
        $random      = rand();
        $addOnLength = 36 - (strlen($idPrefix) + 1);

        return $idPrefix . '_' . substr(sha1($newDateTime->getTimestamp() . $random), 0, $addOnLength);
    }
}
