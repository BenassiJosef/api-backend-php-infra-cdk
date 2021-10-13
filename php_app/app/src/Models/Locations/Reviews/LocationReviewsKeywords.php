<?php


namespace App\Models\Locations\Reviews;

use Doctrine\ORM\Mapping as ORM;

/**
 * LocationReviewsKeywords
 *
 * @ORM\Table(name="location_reviews_keywords")
 * @ORM\Entity
 */
class LocationReviewsKeywords
{

    public function __construct(string $reviewCommentId, string $text, float $score)
    {
        $this->reviewCommentId  = $reviewCommentId;
        $this->text = $text;
        $this->score = $score;
    }


    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="reviewCommentId", type="string")
     */

    private $reviewCommentId;

    /**
     * @var string
     * @ORM\Column(name="text", type="string")
     */
    private $text;


    /**
     * @var float
     * @ORM\Column(name="score", type="float")
     */
    private $score;


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
