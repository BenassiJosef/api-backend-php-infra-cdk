<?php
/**
 * Created by jamieaitken on 06/08/2018 at 11:04
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Reviews;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocationReviewsTimeline
 *
 * @ORM\Table(name="location_reviews_timeline")
 * @ORM\Entity
 */
class LocationReviewsTimeline
{

    public function __construct(string $reviewId)
    {
        $this->reviewId         = $reviewId;
        $this->createdAt        = new \DateTime();
        $this->overallRating    = 0;
        $this->oneStarRatings   = 0;
        $this->twoStarRatings   = 0;
        $this->threeStarRatings = 0;
        $this->fourStarRatings  = 0;
        $this->fiveStarRatings  = 0;
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
     * @ORM\Column(name="reviewId", type="string")
     */

    private $reviewId;

    /**
     * @var float
     * @ORM\Column(name="overallRating", type="float")
     */
    private $overallRating;

    /**
     * @var float
     * @ORM\Column(name="oneStarRatings", type="float")
     */

    private $oneStarRatings;

    /**
     * @var float
     * @ORM\Column(name="twoStarRatings", type="float")
     */

    private $twoStarRatings;

    /**
     * @var float
     * @ORM\Column(name="threeStarRatings", type="float")
     */

    private $threeStarRatings;

    /**
     * @var float
     * @ORM\Column(name="fourStarRatings", type="float")
     */

    private $fourStarRatings;

    /**
     * @var float
     * @ORM\Column(name="fiveStarRatings", type="float")
     */

    private $fiveStarRatings;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

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