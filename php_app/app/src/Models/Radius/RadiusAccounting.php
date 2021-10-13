<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 01/09/2017
 * Time: 18:15
 */

namespace App\Models\Radius;

use Doctrine\ORM\Mapping as ORM;
/**
 * Class RadiusAccounting
 * @package App\Models
 *
 * @ORM\Table(name="radacct")
 * @ORM\Entity
 */
class RadiusAccounting
{
    /**
     * @var integer
     *
     * @ORM\Column(name="radacctid", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $radacctid;

    /**
     * @var string
     *
     * @ORM\Column(name="acctsessionid", type="string")
     */
    private $acctSessionId;

    /**
     * @var string
     *
     * @ORM\Column(name="acctuniqueid", type="string")
     */
    private $acctUniqueId;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string")
     */
    private $userName;

    /**
     * @var string
     *
     * @ORM\Column(name="groupname", type="string")
     */
    private $groupName;

    /**
     * @var string
     *
     * @ORM\Column(name="realm", type="string")
     */
    private $realm;

    /**
     * @var string
     *
     * @ORM\Column(name="nasipaddress", type="string")
     */
    private $nasIpAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="nasportid", type="string")
     */
    private $nasPortId;

    /**
     * @var string
     *
     * @ORM\Column(name="nasporttype", type="string")
     */
    private $nasPortType;

    /**
     * @var integer
     *
     * @ORM\Column(name="acctstarttime", type="datetime")
     */
    private $acctStartTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="acctupdatetime", type="datetime")
     */
    private $acctUpdateTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="acctstoptime", type="datetime")
     */
    private $acctStopTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="acctsessiontime", type="integer")
     */
    private $acctSessionTime;

    /**
     * @var string
     *
     * @ORM\Column(name="acctauthentic", type="string")
     */
    private $acctAuthentic;

    /**
     * @var string
     *
     * @ORM\Column(name="connectinfo_start", type="string")
     */
    private $connectInfoStart;

    /**
     * @var string
     *
     * @ORM\Column(name="connectinfo_stop", type="string")
     */
    private $connectInfoStop;

    /**
     * @var integer
     * @ORM\Column(name="acctinputoctets", type="integer")
     */
    private $acctInputOctets;

    /**
     * @var integer
     * @ORM\Column(name="acctoutputoctets", type="integer")
     */
    private $acctOutputOctets;

    /**
     * @var string
     *
     * @ORM\Column(name="calledstationid", type="string")
     */
    private $calledStationId;

    /**
     * @var string
     *
     * @ORM\Column(name="callingstationid", type="string")
     */
    private $callingStationId;

    /**
     * @var string
     *
     * @ORM\Column(name="acctterminatecause", type="string")
     */
    private $acctTerminateCause;

    /**
     * @var string
     *
     * @ORM\Column(name="servicetype", type="string")
     */
    private $seviceType;

    /**
     * @var string
     *
     * @ORM\Column(name="framedprotocol", type="string")
     */
    private $framedProtocol;

    /**
     * @var string
     *
     * @ORM\Column(name="framedipaddress", type="string")
     */
    private $framedIpAddress;

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