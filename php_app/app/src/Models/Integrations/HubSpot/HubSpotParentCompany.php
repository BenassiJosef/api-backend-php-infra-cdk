<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 04/08/2017
 * Time: 09:50
 */

namespace App\Models\Integrations\HubSpot;

use Doctrine\ORM\Mapping as ORM;

/**
 * HubSpotParentCompany
 *
 * @ORM\Table(name="hubspot_parent_company")
 * @ORM\Entity
 */
class HubSpotParentCompany
{
    public function __construct(string $uid, string $hubSpotCompanyId, string $type)
    {
        $this->uid              = $uid;
        $this->hubSpotCompanyId = $hubSpotCompanyId;
        $this->type             = $type;
    }

    /**
     * @var integer
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="uid", type="string", nullable=false)
     */
    private $uid;

    /**
     * @var string
     * @ORM\Column(name="hubspotCompanyId", type="string")
     */
    private $hubSpotCompanyId;

    /**
     * @var string
     * @ORM\Column(name="type", type="string")
     */
    private $type;

    /**
     * @var integer
     * @ORM\Column(name="mrr", type="integer")
     */
    private $mrr;

    /**
     * @var integer
     * @ORM\Column(name="arr", type="integer")
     */
    private $arr;

    /**
     * @var string
     * @ORM\Column(name="discount", type="string")
     */
    private $discount;

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