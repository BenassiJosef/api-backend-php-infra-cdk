<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 17/10/2017
 * Time: 10:59
 */

namespace App\Models\Locations\Marketing;

use Doctrine\ORM\Mapping as ORM;

/**
 * MarketingLocations
 *
 * @ORM\Table(name="marketing_message_preview")
 * @ORM\Entity
 */
class MarketingMessagePreview
{

    public function __construct($messageId)
    {
        $this->id   = $messageId;
        $this->sent = 1;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=36, nullable=false)
     * @ORM\Id
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="sent", type="integer")
     */
    private $sent;

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