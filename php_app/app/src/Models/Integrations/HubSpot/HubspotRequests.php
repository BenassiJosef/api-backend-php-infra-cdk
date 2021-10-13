<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 31/08/2017
 * Time: 14:19
 */

namespace App\Models\Integrations\HubSpot;

use Doctrine\ORM\Mapping as ORM;

/**
 * HubSpotParentCompany
 *
 * @ORM\Table(name="hubspot_requests")
 * @ORM\Entity
 */
class HubspotRequests
{
    /**
     * @var integer
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Id
     */
    private $id;

    /**
     * @var integer
     * @ORM\Column(name="offset", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $offset;

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