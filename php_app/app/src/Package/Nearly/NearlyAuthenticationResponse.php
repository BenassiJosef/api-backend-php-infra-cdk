<?php

namespace App\Package\Nearly;

use App\Models\Locations\Vendors;
use App\Package\Nearly\NearlyInput;
use JsonSerializable;

class NearlyAuthenticationResponse implements JsonSerializable
{
    /**
     * MarketingController constructor.
     * @param NearlyInput $input
     */
    public function __construct(
        NearlyInput $input,
        Vendors $vendor

    ) {
        $this->input = $input;
        $this->vendor = $vendor;
    }

    /**
     * @var NearlyInput $input
     */
    private $input;

    /**
     * @var Vendors $vendor
     */
    private $vendor;

    /**
     * @var string $redirectUri
     */
    private $redirectUri;

    /**
     * @var string $landingUri
     */
    private $landingUri;

    /**
     * @var string $username
     */
    private $username;

    /**
     * @var bool $online
     */
    private $online = true;

    public function getPassword(): string
    {
        return $this->input->getProfileId();
    }

    public function getUsername(): string
    {
        return $this->input->getProfileId() . $this->input->getSerial();
    }

    public function getLandingUri(): string
    {
        return 'http://nearly.online/landing/' . $this->input->getSerial();
    }

    public function getRedirectUri(): ?string
    {

        if ($this->vendor->getKey() === 'mikrotik') {
            return $this->redirectUri;
        }

        if ($this->vendor->getAuthMethod() === 'wispr') {
            return $this->input->getAp();
        }
        if ($this->vendor->getKey() === 'draytek') {
            return $this->input->getAp() . '?username=' .
                $this->getUsername() . '&password=' . $this->getPassword();
        }
        if ($this->vendor->getKey() === 'ruckus_unleashed') {
            return $this->input->getAp() . '?username=' .
                $this->getUsername() . '&password=' . $this->getPassword();
        }
        if ($this->vendor->getAuthMethod() === 'link' && $this->vendor->getRadius()) {
            return 'http://' . $this->input->getAp() . '/login?username=' .
                $this->getUsername() . '&password=' . $this->getPassword();
        }
        if ($this->vendor->getAuthMethod() === 'link' && !$this->vendor->getRadius()) {
            return $this->input->getAp();
        }

        return $this->redirectUri;
    }

    public function setRedirectUri(string $redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    public function setOnline(bool $online)
    {
        $this->online = $online;
    }

    public function getMikrotikRedirect(string $type)
    {
        $token = 'TXGZ3SbcFM6VHQCBGKuYPhiRZcwyn8UevMdNJ3DXL4MyFDnSXi';
        $username = 'freewifi';

        if ($type === 'paid') {
            $token = 'paidpass';
            $username = "paid";
        }
        $link = $this->input->getAp();
        if (is_null($link)) {
            $link = 'http://10.4.1.2/login';
        }

        $this->setRedirectUri($link . "?username=" . $username . "&password=" . $token);
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            'serial' => $this->input->getSerial(),
            'method' => $this->vendor->getKey(),
            'auth_method' => $this->vendor->getAuthMethod(),
            'redirect_uri' => $this->getRedirectUri(),
            'landing_uri' => $this->getLandingUri(),
            'username' => $this->getUsername(),
            'password' => $this->getPassword(),
            'online' => $this->online,
            'ip' => $this->input->getIp(),
            'mac' => $this->input->getMac(),
        ];
    }
}
