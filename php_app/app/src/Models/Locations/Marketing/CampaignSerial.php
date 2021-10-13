<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/03/2017
 * Time: 12:23
 */

namespace App\Models\Locations\Marketing;
use App\Models\MarketingCampaigns;
use Doctrine\ORM\Mapping as ORM;
/**
 * Class CampaignSerial
 * @package App\Models\Locations\Marketing
 *
 * @ORM\Table(name="marketing_campaign_serials")
 * @ORM\Entity
 */
class CampaignSerial
{

    public function __construct($campaignId, $serial)
    {
        $this->campaignId = $campaignId;
        $this->serial     = $serial;
    }

    /**
     * @var string
     * @ORM\Column(name="id", type="guid", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id = '';

    /**
     * @var string
     * @ORM\Column(name="campaignId", type="string", length=36)
     */
    private $campaignId;

    /**
     * @var string
     * @ORM\Column(name="serial", type="string", length=12)
     */
    private $serial;

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