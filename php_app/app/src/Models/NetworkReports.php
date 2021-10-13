<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * NetworkReports
 *
 * @ORM\Table(name="network_reports", indexes={@ORM\Index(name="serial", columns={"serial"}), @ORM\Index(name="timestamp", columns={"timestamp"})})
 * @ORM\Entity
 */
class NetworkReports
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=30, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id = '';

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=true)
     */
    private $serial;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timestamp", type="datetime", nullable=true)
     */
    private $timestamp;

    /**
     * @var integer
     *
     * @ORM\Column(name="logins", type="integer", nullable=true)
     */
    private $logins;

    /**
     * @var integer
     *
     * @ORM\Column(name="registrations", type="integer", nullable=true)
     */
    private $registrations;

    /**
     * @var integer
     *
     * @ORM\Column(name="download", type="integer", nullable=true)
     */
    private $download;

    /**
     * @var integer
     *
     * @ORM\Column(name="upload", type="integer", nullable=true)
     */
    private $upload;

    /**
     * @var string
     *
     * @ORM\Column(name="uptime", type="string", length=25, nullable=true)
     */
    private $uptime;

    /**
     * @var integer
     *
     * @ORM\Column(name="auth_time", type="integer", nullable=true)
     */
    private $authTime;


}

