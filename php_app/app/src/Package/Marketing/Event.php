<?php

namespace App\Package\Marketing;

use DateTime;
use JsonSerializable;

class Event implements JsonSerializable
{
    /**
     * @var int $profileId
     */
    private $profileId;

    /**
     * @var string $info
     */
    private $info;

    /**
     * @var DateTime $eventCreatedAt
     */
    private $eventCreatedAt;

    /**
     * @var string $email
     */
    private $email;

    /**
     * @var string $first
     */
    private $first;

    /**
     * @var string $last
     */
    private $last;

    /**
     * Total constructor.
     * @param string $event
     * @param int $count
     * @param int $uniqueCount
     */
    public function __construct(
        int $profileId,
        string $info,
        int $eventCreatedAt,
        ?string $email,
        ?string $first,
        ?string $last
    ) {
        $this->profileId           = $profileId;
        $this->info    = $info;
        $this->email    = $email;
        $this->first    = $first;
        $this->last    = $last;
        $time = new DateTime();
        $this->eventCreatedAt = $time->setTimestamp($eventCreatedAt);
    }

    public function getInfo()
    {
        if (strpos($this->info, 'https://api.stampede.ai') === false) {
            return $this->info;
        }

        $parsedUrl = parse_url($this->info);
        $res = [];
        parse_str($parsedUrl['query'], $res);
        if (array_key_exists('url', $res)) {
            return $res['url'];
        }
        return $this->info;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            'profileId'                      => $this->profileId,
            'info'               => $this->getInfo(),
            'event_created_at'      => $this->eventCreatedAt,
            'email' => $this->email,
            'first' => $this->first,
            'last' => $this->last
        ];
    }
}
