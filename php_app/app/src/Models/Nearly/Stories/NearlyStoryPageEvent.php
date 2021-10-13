<?php
/**
 * Created by jamieaitken on 07/03/2019 at 13:41
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Models\Nearly\Stories;


use Doctrine\ORM\Mapping as ORM;

/**
 * NearlyStoryPageEvent
 *
 * @ORM\Table(name="nearly_story_page_event")
 * @ORM\Entity
 */
class NearlyStoryPageEvent
{

    static $pageToEvent = [
        'title'        => 'Title',
        'subtitle'     => 'Subtitle',
        'imgSrc'       => 'Image',
        'linkUrl'      => 'URL Link',
        'linkText'     => 'Text for Link',
        'style'        => 'Page Theme',
        'pageNumber'   => 'Order',
        'emailSubject' => 'Email Subject'
    ];

    public function __construct(string $pageId, string $description)
    {
        $date              = new \DateTime();
        $this->createdAt   = new \DateTime($date->format('Y-m-d H:00:00'));
        $this->pageId      = $pageId;
        $this->description = $description;
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
     * @ORM\Column(name="description", type="string")
     */
    private $description;

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