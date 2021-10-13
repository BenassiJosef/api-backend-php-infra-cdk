<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 22/05/2017
 * Time: 13:18
 */

namespace App\Models\Integrations\Hooks;

use Doctrine\ORM\Mapping as ORM;

/**
 * Generic
 *
 * @ORM\Table(name="hook")
 * @ORM\Entity
 */
class Hook
{

    public function __construct(string $target_url, $createdBy, $event)
    {
        $this->target_url = $target_url;
        $this->createdBy  = $createdBy;
        $this->event      = $event;
        $this->deleted    = false;
        $this->createdAt  = new \DateTime();
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
     * @ORM\Column(name="target_url", type="string")
     */
    private $target_url;

    /**
     * @var string
     * @ORM\Column(name="event", type="string")
     */
    private $event;

    /**
     * @var string
     * @ORM\Column(name="createdBy", type="string")
     */
    private $createdBy;

    /**
     * @var string
     * @ORM\Column(name="param", type="string")
     */
    private $param;

    /**
     * @var boolean
     * @ORM\Column(name="deleted", type="boolean")
     */
    private $deleted;

    /**
     * @var /DateTime
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