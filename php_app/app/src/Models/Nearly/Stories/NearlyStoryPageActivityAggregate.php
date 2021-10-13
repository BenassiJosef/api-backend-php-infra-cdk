<?php
/**
 * Created by jamieaitken on 07/03/2019 at 09:45
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Models\Nearly\Stories;

use Doctrine\ORM\Mapping as ORM;

/**
 * NearlyStoryPageActivityAggregate
 *
 * @ORM\Table(name="nearly_story_page_activity_aggregate")
 * @ORM\Entity
 */
class NearlyStoryPageActivityAggregate
{

    public function __construct(string $pageId, string $serial)
    {
        $this->formattedTimestamp = new \DateTime();
        $this->formattedTimestamp = new \DateTime($this->formattedTimestamp->format('Y-m-d H:00:00'));
        $this->impressions        = 0;
        $this->clicks             = 0;
        $this->conversions        = 0;
        $this->serial             = $serial;
        $this->pageId             = $pageId;
        $this->isArchived         = false;
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
     * @ORM\Column(name="pageId", type="string")
     */
    private $pageId;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string")
     */
    private $serial;

    /**
     * @var integer
     * @ORM\Column(name="impressions", type="integer")
     */
    private $impressions;

    /**
     * @var integer
     * @ORM\Column(name="clicks", type="integer")
     */
    private $clicks;

    /**
     * @var integer
     * @ORM\Column(name="conversions", type="integer")
     */
    private $conversions;

    /**
     * @var \DateTime
     * @ORM\Column(name="formattedTimestamp", type="datetime")
     */
    private $formattedTimestamp;

    /**
     * @var boolean
     * @ORM\Column(name="isArchived", type="boolean")
     */
    private $isArchived;

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