<?php

namespace App\Package\Nearly;

class NearlyVendor
{


    public function __construct(string $vendor)
    {
        $this->vendor = $vendor;
    }

    /**
     * @var string
     */
    private $vendor;

    /** 
     * @var bool
     */
    private $radius = false;

    public function isGenericAuth(): bool
    {
        if ($this->vendor === 'ruckus-unleashed' || $this->vendor === 'aerohive') {
            return true;
        }
        return false;
    }

    public function isGenericRadius(): bool
    {
        if ($this->vendor === 'ligowave') {
            return true;
        }
        return false;
    }

    public function isRadius(): bool
    {
        if (
            $this->vendor === 'ruckus-smartzone' ||
            $this->vendor === 'ruckus-unleashed' ||
            $this->vendor === 'meraki' ||
            $this->vendor === 'ruckus' ||
            $this->vendor === 'aerohive' ||
            $this->vendor === 'openmesh' ||
            $this->vendor === 'engenius' ||
            $this->vendor === 'ligowave' ||
            $this->vendor === 'zyxel-nebula' ||
            $this->vendor === 'plasmacloud' ||
            $this->vendor === 'dlink' ||
            $this->vendor === 'tplink' ||
            $this->vendor === 'aruba'
        ) {
            return true;
        }
        return false;
    }

    public function isWispr(): bool
    {

        $method = $this->vendor;

        if (
            $method === 'radius' ||
            $method === 'dlink' ||
            $method === 'tplink' ||
            $method === 'ruckus-smartzone' ||
            $method === 'ruckus' ||
            $method === 'meraki' ||
            $method === 'engenius'
        ) {
            return true;
        }
        return false;
    }

    public function requiresChallenge(): bool
    {
        if (
            $this->vendor === 'openmesh' ||
            $this->vendor === 'ligowave' ||
            $this->vendor === 'radius'
        ) {
            return true;
        }
        return false;
    }

    public function getMethod(): string
    {
        return $this->vendor;
    }

    public function setRadius(bool $radius)
    {
        $this->radius = $radius;
    }
    public function getRadius(): bool
    {
        return $this->radius;
    }
}
