<?php

/**
 * Created by jamieaitken on 06/12/2017 at 17:35
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Models\Marketing;

use App\Utils\Strings;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * TemplateSettings
 *
 * @ORM\Table(name="oauth_user_templates_settings")
 * @ORM\Entity
 */
class TemplateSettings implements JsonSerializable
{

    static $immutableKeys = [
        'id',
        'uid',
        'deleted'
    ];

    static $getKeys = 'ts.logo, ts.twitterUrl, ts.facebookUrl, ts.youtubeUrl, ts.linkedInUrl, ts.instagramUrl, ts.tripAdvisorUrl, ts.id,
    ts.company, ts.line1, ts.line2, ts.city, ts.sendFrom, ts.replyTo';

    private static $setterMap = [
        'send_from'         => 'setSendFrom',
        'reply_to' => 'setReplyTo',
    ];

    /**
     * @param array $data
     * @return self
     */
    public function updateFromArray(array $data): self
    {
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, self::$setterMap)) {
                continue;
            }
            $setter = self::$setterMap[$key];
            $this->$setter($value);
        }
        return $this;
    }


    public function __construct(
        string $sendFrom,
        string $replyTo,
        string $id = null
    ) {
        if (is_null($id)) {
            $this->id = Strings::idGenerator('mkTpl');
        } else {
            $this->id = $id;
        }
        $this->sendFrom       = $sendFrom;
        $this->replyTo        = $replyTo;
        $this->deleted        = false;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="logo", type="string")
     */
    private $logo;

    /**
     * @var string
     * @ORM\Column(name="twitterUrl", type="string")
     */
    private $twitterUrl;

    /**
     * @var string
     * @ORM\Column(name="facebookUrl", type="string")
     */
    private $facebookUrl;

    /**
     * @var string
     * @ORM\Column(name="youtubeUrl", type="string")
     */
    private $youtubeUrl;

    /**
     * @var string
     * @ORM\Column(name="linkedInUrl", type="string")
     */
    private $linkedInUrl;

    /**
     * @var string
     * @ORM\Column(name="tripAdvisorUrl", type="string")
     */
    private $tripAdvisorUrl;

    /**
     * @var string
     * @ORM\Column(name="company", type="string")
     */
    private $company;

    /**
     * @var string
     * @ORM\Column(name="line1", type="string")
     */
    private $line1;

    /**
     * @var string
     * @ORM\Column(name="line2", type="string")
     */
    private $line2;

    /**
     * @var string
     * @ORM\Column(name="city", type="string")
     */
    private $city;

    /**
     * @var string
     * @ORM\Column(name="sendFrom", type="string")
     */
    private $sendFrom;

    /**
     * @var string
     * @ORM\Column(name="replyTo", type="string")
     */
    private $replyTo;

    /**
     * @var string
     * @ORM\Column(name="instagramUrl", type="string")
     */
    private $instagramUrl;

    /**
     * @var boolean
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;

    /**
     * @return array
     */

    public function getId(): string
    {
        return $this->id;
    }

    public function getSendFrom(): ?string
    {
        return $this->sendFrom;
    }

    public function getReplyTo(): ?string
    {
        return $this->replyTo;
    }

    public function setReplyTo(string $replyTo)
    {
        $this->replyTo = $replyTo;
    }

    public function setSendFrom(string $sendFrom)
    {
        $this->sendFrom = $sendFrom;
    }

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }

    public function emailArray()
    {
        return [
            'send_to' => $this->jsonSerialize()
        ];
    }

    public function jsonSerialize()
    {
        return [
            "id"        => $this->getId(),
            "send_from"  => $this->getSendFrom(),
            "reply_to" => $this->getReplyTo(),
        ];
    }
}
