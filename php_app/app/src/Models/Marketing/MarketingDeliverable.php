<?php

/**
 * Created by jamieaitken on 2019-06-13 at 12:25
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Models\Marketing;

use Doctrine\ORM\Mapping as ORM;

/**
 * MarketingDeliverable
 * @ORM\Table(name="marketing_deliverability")
 * @ORM\Entity
 */
class MarketingDeliverable
{

    public function __construct(string $type, string $messageId, ?int $profileId, ?string $serial, ?string $templateType, ?string $campaignId)
    {
        $this->type         = $type;
        $this->messageId    = $messageId;
        $this->profileId    = $profileId;
        $this->serial       = $serial;
        $this->templateType = $templateType;
        $this->campaignId   = $campaignId;
        $this->createdAt    = new \DateTime();
    }

    /**
     * @var string
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string")
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(name="messageId", type="string", nullable=true)
     */
    private $messageId;

    /**
     * @var int
     * @ORM\Column(name="profileId", type="integer", nullable=true)
     */
    private $profileId;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string")
     */
    private $serial;

    /**
     * @var string
     * @ORM\Column(name="templateType", type="string")
     */
    private $templateType;

    /**
     * @var string
     * @ORM\Column(name="campaignId", type="string")
     */
    private $campaignId;

    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return array
     */

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
}
