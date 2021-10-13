<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 25/01/2017
 * Time: 11:44
 */

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class LandingRequest
 * @package App\Models
 *
 * @ORM\Table(name="landing_request")
 * @ORM\Entity
 */
class LandingRequest
{
    public function __construct($serial, $url)
    {
        $this->serial = $serial;
        $this->url    = $url;
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
     * @ORM\Column(name="serial", type="string")
     */

    private $serial;

    /**
     * @var string
     * @ORM\Column(name="url", type="string")
     */

    private $url;

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