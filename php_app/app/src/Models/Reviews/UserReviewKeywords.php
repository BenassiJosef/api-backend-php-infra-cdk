<?php


namespace App\Models\Reviews;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Ramsey\Uuid\Uuid;

/**
 * UserReviewKeywords
 *
 * @ORM\Table(name="user_review_keywords")
 * @ORM\Entity
 */
class UserReviewKeywords implements JsonSerializable
{

    public function __construct(UserReview $review, string $text, float $score)
    {
        $this->id =    Uuid::uuid4();
        $this->userReview  = $review;
        $this->userReviewId = $review->getId();
        $this->text = $text;
        $this->score = $score;
    }

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="uuid")
     * @var UuidInterface $id
     */
    private $id;

    /**
     * @var UuidInterface $userReviewId
     * @ORM\Column(name="user_review_id", type="uuid")
     */

    private $userReviewId;

    /**
     * @ORM\ManyToOne(targetEntity="UserReview", cascade={"persist"})
     * @ORM\JoinColumn(name="user_review_id", referencedColumnName="id", nullable=false)
     * @var UserReview $userReview
     */
    private $userReview;

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

    public function jsonSerialize()
    {
        return [
            "id"        => $this->id,
            'text'          => $this->text,
            "score" => $this->score,
        ];
    }
}
