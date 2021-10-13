<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/03/2017
 * Time: 14:04
 */

namespace App\Models\Locations\Marketing;

use Doctrine\ORM\Mapping as ORM;
/**
 * Class CampaignEvents
 * @package App\Models\Locations\Marketing
 *
 * @ORM\Table(name="marketing_campaign_events")
 * @ORM\Entity
 */

class CampaignEvents
{

    public function __construct($name)
    {
        $this->name    = $name;
        $this->created = new \DateTime();
    }

    /**
     * @var string
     * @ORM\Column(name="id", type="guid", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id = '';

    /**
     * @var \DateTime
     * @ORM\Column(name="created", type="DateTime")
     */
    private $created;

    /**
     * @var string
     * @ORM\Column(name="name", type="string")
     */
    private $name;

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