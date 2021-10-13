<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 09/01/2017
 * Time: 19:26
 */

namespace App\Controllers\Integrations\Facebook;

use Facebook\PersistentData\PersistentDataInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class _FacebookSessionHandler implements PersistentDataInterface
{

    private $session;

    protected $sessionPrefix = 'FBRLH_';

    public function __construct()
    {
        $this->session = new Session();
    }

    public function get($key)
    {
        return $this->session->get($this->sessionPrefix . $key);
    }

    public function set($key, $value)
    {
        $this->session->set($this->sessionPrefix . $key, $value);
    }
}
