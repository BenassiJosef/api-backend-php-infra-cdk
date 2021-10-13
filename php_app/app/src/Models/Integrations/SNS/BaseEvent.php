<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 22/05/2017
 * Time: 13:18
 */

namespace App\Models\Integrations\SNS;

use Doctrine\ORM\Mapping as ORM;

/**
 * Generic
 *
 * @ORM\Table(name="aws_base_events")
 * @ORM\Entity
 */
class BaseEvent
{

    public function __construct(string $name, int $minimumRole)
    {
        $this->name        = $name;
        $this->minimumRole = $minimumRole;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var integer
     * @ORM\Column(name="minRole", type="integer")
     */
    private $minimumRole;

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