<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * NetworkClients
 *
 * @ORM\Table(name="network_clients")
 * @ORM\Entity
 */
class NetworkClients
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
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=true)
     */
    private $timestamp;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=true)
     */
    private $serial;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=15, nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=100, nullable=true)
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(name="mac", type="string", length=17, nullable=true)
     */
    private $mac;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=17, nullable=true)
     */
    private $ip;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    private $status;


}

