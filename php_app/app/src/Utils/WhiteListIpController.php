<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 16/02/2017
 * Time: 14:32
 */

namespace App\Utils;

class WhiteListIpController
{
    protected $ip;

    public function __construct($ipToTest)
    {
        $this->ip = $ipToTest;
    }

    public function verify()
    {
        $ipArr = [
            '54.187.174.169',
            '54.187.205.235',
            '54.187.216.72',
            '54.241.31.99',
            '54.241.31.102',
            '54.241.34.107'
        ];
        if (in_array($this->ip, $ipArr)) {
            return true;
        }
        return false;
    }
}
