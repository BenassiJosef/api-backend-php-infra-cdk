<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/03/2017
 * Time: 15:08
 */

namespace App\Models\Locations\Marketing;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class CampaignMessage
 * @package App\Models\Locations\Marketing
 *
 * @ORM\Table(name="marketing_campaign_messages")
 * @ORM\Entity
 */
class CampaignMessage
{


    public function __construct($subject)
    {
        $this->subject = $subject;
        $this->created = new \DateTime();
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="guid", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    private $id = '';

    /**
     * @var \DateTime
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    /**
     * @var string
     * @ORM\Column(name="subject", type="string")
     */
    private $subject;

    /**
     * @var string
     * @ORM\Column(name="smsContents", type="string")
     */
    private $smsContents;

    /**
     * @var string
     * @ORM\Column(name="emailContents", type="string")
     */
    private $emailContents;

    /**
     * @var string
     * @ORM\Column(name="smsSender", type="string")
     */
    private $smsSender;

    /**
     * @var string
     * @ORM\Column(name="sent", type="string")
     */
    private $sent;

    /**
     * @var string
     * @ORM\Column(name="templateType", type="string")
     */
    private $templateType;

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