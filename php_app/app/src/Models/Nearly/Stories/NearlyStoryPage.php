<?php

/**
 * Created by jamieaitken on 06/03/2019 at 15:01
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Models\Nearly\Stories;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * NearlyStoryPage
 *
 * @ORM\Table(name="nearly_story_page")
 * @ORM\Entity
 */
class NearlyStoryPage implements JsonSerializable
{

    static $KEYS = ['pageNumber', 'title', 'subtitle', 'style', 'imgSrc', 'linkUrl', 'linkText'];

    static $THEME_TEMPLATE_KEYS = [
        'Clean'   => 'NearlyStoryClean',
        'Default' => 'NearlyStoryDefault',
        'Minimal' => 'NearlyStoryMinimal'
    ];

    public function __construct(
        string $storyId,
        int $pageNumber,
        ?string $title,
        ?string $subtitle,
        string $style,
        string $imgSrc,
        ?string $linkUrl,
        ?string $linkText
    ) {
        $this->storyId    = $storyId;
        $this->pageNumber = $pageNumber;
        $this->title      = $title;
        $this->subtitle   = $subtitle;
        $this->style      = $style;
        $this->imgSrc     = $imgSrc;
        $this->linkUrl    = $linkUrl;
        $this->linkText   = $linkText;
        $this->isArchived = false;
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
     * @ORM\Column(name="storyId", type="string")
     */
    private $storyId;

    /**
     * @var integer
     * @ORM\Column(name="pageNumber", type="integer")
     */
    private $pageNumber;

    /**
     * @var string
     * @ORM\Column(name="title", type="string")
     */
    private $title;

    /**
     * @var string
     * @ORM\Column(name="subtitle", type="string")
     */
    private $subtitle;

    /**
     * @var string
     * @ORM\Column(name="style", type="string")
     */
    private $style;

    /**
     * @var string
     * @ORM\Column(name="imgSrc", type="string")
     */
    private $imgSrc;

    /**
     * @var string
     * @ORM\Column(name="linkUrl", type="string")
     */
    private $linkUrl;

    /**
     * @var string
     * @ORM\Column(name="linkText", type="string")
     */
    private $linkText;

    /**
     * @var boolean
     * @ORM\Column(name="isArchived", type="boolean")
     */
    private $isArchived;

    /**
     * @var string
     */
    private $trackingId;

    public function getId(): string
    {
        return $this->id;
    }

    public function setTrackingId(string $trackingId)
    {
        $this->trackingId = $trackingId;
    }

    public function getImageSrc(): ?string
    {
        return $this->imgSrc;
    }

    public function setImageSrc(?string $imageSrc)
    {
        $this->imgSrc = $imageSrc;
    }

    public function jsonSerialize()
    {
        return [
            'id'    => $this->id,
            'story_id'    => $this->storyId,
            'page_number'     => $this->pageNumber,
            'title' => $this->title,
            'sub_title' => $this->subtitle,
            'style' => $this->style,
            'img_src' => $this->imgSrc,
            'link_uri' => $this->linkUrl,
            'link_text' => $this->linkText,
            'tracking_id' => $this->trackingId
        ];
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
