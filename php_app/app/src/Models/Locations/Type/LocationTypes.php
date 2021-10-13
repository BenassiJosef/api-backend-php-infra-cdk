<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 25/01/2017
 * Time: 11:44
 */

namespace App\Models\Locations\Type;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class LocationTypes
 * @package App\Models
 *
 * @ORM\Table(name="location_types")
 * @ORM\Entity
 */
class LocationTypes
{

    public function __construct(string $name, string $sicCode)
    {
        $this->name    = $name;
        $this->sicCode = $sicCode;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid", length=36, nullable=false)
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
     * @var string
     * @ORM\Column(name="sic_code", type="string")
     */

    private $sicCode;

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