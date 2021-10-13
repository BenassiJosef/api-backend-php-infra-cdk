<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 19/04/2017
 * Time: 03:02
 */

namespace App\Controllers\Integrations\Mixpanel;

class _Mixpanel
{

    protected $mp;

    public function __construct()
    {
        $this->mp = \Mixpanel::getInstance('37f00e007a7ab2371ca2a56f05063cdf');
    }

    public function identify(string $id = '')
    {
        $this->mp->identify($id);

        return $this;
    }

    public function track(string $event = '', array $data = [])
    {
        $this->mp->track($event, $data);

        return $this;
    }

    public function register($key, $value)
    {
        $this->mp->register($key, $value);

        return $this;
    }

    public function setProfile($uid, array $properties)
    {
        $this->mp->people->set($uid, $properties, 0, true);

        return $this;
    }

    public function increment($uid, string $event, $value)
    {
        $this->mp->people->increment($uid, $event, $value);

        return $this;
    }

    public function appendProfile($uid, string $property, $value)
    {
        $this->mp->people->append($uid, $property, $value);

        return $this;
    }
}
