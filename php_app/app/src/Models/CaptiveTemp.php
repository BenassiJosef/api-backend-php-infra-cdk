<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * CaptiveTemp
 *
 * @ORM\Table(name="captive_temp", indexes={@ORM\Index(name="serial", columns={"serial"})})
 * @ORM\Entity
 */
class CaptiveTemp
{
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
     *
     * @ORM\Column(name="mac", type="string", length=17, nullable=true)
     */
    private $mac;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=true)
     */
    private $serial;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=false)
     */
    private $timestamp = 'CURRENT_TIMESTAMP';

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=17, nullable=true)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="wan_ip", type="string", length=25, nullable=true)
     */
    private $wanIp;

    /**
     * @var integer
     *
     * @ORM\Column(name="v", type="integer", nullable=true)
     */
    private $v;


}

