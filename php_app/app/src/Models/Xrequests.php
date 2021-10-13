<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * Xrequests
 *
 * @ORM\Table(name="xrequests", indexes={@ORM\Index(name="ts", columns={"ts"}), @ORM\Index(name="originip", columns={"originip"})})
 * @ORM\Entity
 */
class Xrequests
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
     * @ORM\Column(name="originip", type="string", length=45, nullable=false)
     */
    private $originip = '';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ts", type="datetime", nullable=false)
     */
    private $ts = 'CURRENT_TIMESTAMP';


}

