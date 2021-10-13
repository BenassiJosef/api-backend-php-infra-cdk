<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Whitelist
 *
 * @ORM\Table(name="whitelist")
 * @ORM\Entity
 */
class Whitelist
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
     * @ORM\Column(name="name", type="string", length=200, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="location", type="string", length=200, nullable=true)
     */
    private $location;

    /**
     * @var string
     *
     * @ORM\Column(name="mac", type="string", length=17, nullable=true)
     */
    private $mac;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastseen", type="datetime", nullable=true)
     */
    private $lastseen;


}

