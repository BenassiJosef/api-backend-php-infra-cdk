<?php

namespace App\Package\Nearly;

use App\Utils\MacFormatter;
use Exception;
use JsonSerializable;
use Slim\Http\Request;
use Throwable;

class NearlyInputException extends Exception
{
}

class NearlyInput implements JsonSerializable
{
    private static $mapping = [
        'method'    => 'setMethod',
        'serial'    => 'setSerial',
        'mac'     => 'setMac',
        'ap'   => 'setAp',
        'link'   => 'setAp',
        'ip' => 'setIp',
        'challenge' => 'setChallenge',
        'port' => 'setPort',
        'preview' => 'setPreview',
        'auth_time' => 'setAuthTime',
        'profile_id' => 'setProfileId'
    ];

    /**
     * @param array $input
     * @return static
     * @throws NearlyInputException
     */
    public static function createFromArray(array $input): self
    {
        $nearlyInput = new self();
        foreach (self::$mapping as $field => $method) {
            if (array_key_exists($field, $input)) {
                if ($field === 'preview') {
                    $value =  $input[$field] === 'true' ?  true : false;
                } else {
                    $value = $input[$field];
                }

                $nearlyInput->$method($value);
            }
        }
        return $nearlyInput;
    }


    /**
     * @var string $method
     */
    private $method;

    /**
     * @var string $serial
     */
    private $serial;

    /**
     * @var string $ap
     */
    private $ap;

    /**
     * @var string $ip
     */
    private $ip;

    /**
     * @var string $challenge
     */
    private $challenge;

    /**
     * @var string $mac
     */
    private $mac;


    /**
     * @var string $remoteIp
     */
    private $remoteIp;

    /**
     * @var bool $preview
     */
    private $preview = false;

    /**
     * @var bool $marketingOptIn 
     */
    private $marketingOptIn = false;

    /**
     * @var bool $dataOptIn
     */
    private $dataOptIn = false;

    /**
     * @var string $impressionId
     */
    private $impressionId;

    /**
     * @var int $profileId
     */
    private $profileId;

    /**
     * @var int $authTime
     */
    private $authTime = 0;

    public function setRemoteIp(Request $request)
    {
        $remoteIp = $request->getHeader('X-Forwarded-For');
        if (is_array($remoteIp) && !empty($remoteIp)) {
            $remoteIp = $remoteIp[0];
        }
        if (is_string($remoteIp) && stripos($remoteIp, ',') !== false) {
            $mutipleIps = explode(',', $remoteIp);
            if (count($mutipleIps) >= 2) {
                $remoteIp = $mutipleIps[0];
            }
        } else {
            if (is_null($remoteIp) || empty($remoteIp)) {
                $remoteIp = $_SERVER['REMOTE_ADDR'];
            }
        }
        $this->remoteIp = $remoteIp;
    }

    /**
     * @param string $method
     * @return NearlyInput
     */
    private function setMethod(string $method): NearlyInput
    {
        $this->method = $method;
        return $this;
    }
    /**
     * @param string $serial
     * @return NearlyInput
     */
    public function setSerial(string $serial): NearlyInput
    {
        $this->serial = $serial;
        return $this;
    }

    /**
     * @param string $impressionId
     * @return NearlyInput
     */
    public function setImpressionId(?string $impressionId): NearlyInput
    {
        $this->impressionId = $impressionId;
        return $this;
    }

    /**
     * @param int $profileId
     * @return NearlyInput
     */
    public function setProfileId(int $profileId): NearlyInput
    {
        $this->profileId = $profileId;
        return $this;
    }

    /**
     * @param int $marketingOptIn
     * @return NearlyInput
     */
    public function setMarketingOptIn(bool $marketingOptIn): NearlyInput
    {
        $this->marketingOptIn = $marketingOptIn;
        return $this;
    }

    /**
     * @param int $dataOptIn
     * @return NearlyInput
     */
    public function setDataOptIn(bool $dataOptIn): NearlyInput
    {
        $this->dataOptIn = $dataOptIn;
        return $this;
    }

    /**
     * @return bool
     */
    public function getMarketingOptIn(): bool
    {
        return $this->marketingOptIn;
    }

    /**
     * @return bool
     */
    public function getDataOptIn(): bool
    {
        return $this->dataOptIn;
    }

    /**

     * @return string
     */
    public function getImpressionId(): ?string
    {
        return $this->impressionId;
    }

    /**
     * @return int
     */
    public function getProfileId(): ?int
    {
        return $this->profileId;
    }

    /**
     * @param string $ap
     * @return NearlyInput
     */
    private function setAp(string $ap): NearlyInput
    {
        $this->ap = $ap;
        return $this;
    }

    /**
     * @param int $authTime
     * @return NearlyInput
     */
    private function setAuthTime(string $authTime): NearlyInput
    {
        $this->authTime = $authTime;
        return $this;
    }


    /**
     * @param string $mac
     * @return NearlyInput
     */
    public function setMac(string $mac): NearlyInput
    {
        $this->mac = $mac;
        return $this;
    }

    /**
     * @param string $ip
     * @return NearlyInput
     */
    private function setIp(string $ip): NearlyInput
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * @param string $challenge
     * @return NearlyInput
     */
    private function setChallenge(string $challenge): NearlyInput
    {
        $this->challenge = $challenge;
        return $this;
    }

    /**
     * @param bool $preview
     * @return NearlyInput
     */
    private function setPreview(bool $preview): NearlyInput
    {

        $this->preview = $preview;
        return $this;
    }

    /**
     * @param int $port
     * @return NearlyInput
     */
    private function setPort(int $port): NearlyInput
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return string
     */
    public function getRemoteIp(): ?string
    {
        return $this->remoteIp;
    }

    /**
     * @return string
     */
    public function getSerial(): string
    {
        return $this->serial;
    }

    /**
     * @return string
     */
    public function getMac(): string
    {
        return MacFormatter::format($this->mac);
    }

    /**
     * @return string
     */
    public function getShaMac(): string
    {
        return hash('sha512', MacFormatter::format($this->mac));
    }

    /**
     * @return string
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getAp(): ?string
    {
        return $this->ap;
    }

    /**
     * @return int
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @return int
     */
    public function getAuthTime(): int
    {
        return $this->authTime;
    }

    /**
     * @return string
     */
    public function getChallenge(): ?string
    {
        return $this->challenge;
    }

    /**
     * @return bool
     */
    public function getPreview(): bool
    {
        if (is_null($this->preview)) {
            return false;
        }
        return $this->preview;
    }


    public function jsonSerialize()
    {
        return [
            'preview'    => $this->getPreview(),
            'serial'    => $this->getSerial(),
            'challenge'     => $this->getChallenge(),
            'mac'   => $this->getMac()
        ];
    }
}
