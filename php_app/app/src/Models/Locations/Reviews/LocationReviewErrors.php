<?php
/**
 * Created by jamieaitken on 31/10/2018 at 11:15
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Reviews;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocationReviewErrors
 *
 * @ORM\Table(name="location_review_errors")
 * @ORM\Entity
 */
class LocationReviewErrors
{

    public function __construct(
        string $serial,
        string $reviewType,
        string $resource,
        string $errorCode,
        string $errorReason
    ) {
        $this->serial      = $serial;
        $this->reviewType  = $reviewType;
        $this->resource    = $resource;
        $this->errorCode   = $errorCode;
        $this->errorReason = $errorReason;
        $this->createdAt   = new \DateTime();
    }

    /**
     * @var string
     * @ORM\Column(name="id", type="string", length=36)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string")
     */
    private $serial;

    /**
     * @var string
     * @ORM\Column(name="reviewType", type="string")
     */
    private $reviewType;

    /**
     * @var string
     * @ORM\Column(name="resource", type="string")
     */
    private $resource;

    /**
     * @var string
     * @ORM\Column(name="errorCode", type="string")
     */
    private $errorCode;

    /**
     * @var string
     * @ORM\Column(name="errorReason", type="string")
     */
    private $errorReason;

    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

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