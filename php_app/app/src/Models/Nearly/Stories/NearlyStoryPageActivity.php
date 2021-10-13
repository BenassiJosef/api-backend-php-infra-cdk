<?php
/**
 * Created by jamieaitken on 06/03/2019 at 16:11
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Models\Nearly\Stories;


use Doctrine\ORM\Mapping as ORM;

/**
 * NearlyStoryPageActivity
 *
 * @ORM\Table(name="nearly_story_page_activity")
 * @ORM\Entity
 */
class NearlyStoryPageActivity
{

    public function __construct(string $pageId, string $profileId, string $serial)
    {
        $this->serial     = $serial;
        $this->pageId     = $pageId;
        $this->profileId  = $profileId;
        $this->impression = false;
        $this->clicked    = false;
        $this->converted  = false;
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
     * @var string
     * @ORM\Column(name="profileId", type="string")
     */
    private $profileId;

    /**
     * @var boolean
     * @ORM\Column(name="impression", type="boolean")
     */
    private $impression;

    /**
     * @var boolean
     * @ORM\Column(name="clicked", type="boolean")
     */
    private $clicked;

    /**
     * @var boolean
     * @ORM\Column(name="converted", type="boolean")
     */
    private $converted;

    /**
     * @var \DateTime
     * @ORM\Column(name="impressionCreatedAt", type="datetime")
     */
    private $impressionCreatedAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="clickCreatedAt", type="datetime")
     */
    private $clickCreatedAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="conversionCreatedAt", type="datetime")
     */
    private $conversionCreatedAt;

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