<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/09/2017
 * Time: 15:10
 */

namespace App\Models\Notifications;

use Doctrine\ORM\Mapping as ORM;

/**
 * Generic
 *
 * @ORM\Table(name="feature_request_votes")
 * @ORM\Entity
 */
class FeatureRequestVote
{

    public function __construct(string $featureId, string $uid)
    {
        $this->featureId = $featureId;
        $this->uid       = $uid;
        $newDateTime     = new \DateTime();
        $this->timestamp = $newDateTime->getTimestamp();
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
     * @ORM\Column(name="featureId", type="string", nullable=false)
     */
    private $featureId;

    /**
     * @var string
     *
     * @ORM\Column(name="uid", type="string", nullable=false)
     */
    private $uid;

    /**
     * @var integer
     * @ORM\Column(name="timestamp", type="integer")
     */
    private $timestamp;

    /**
     * Get array copy of object
     *
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