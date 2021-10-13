<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * NewFeature
 *
 * @ORM\Table(name="new_feature")
 * @ORM\Entity
 */
class NewFeature
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
     * @ORM\Column(name="timestamp", type="date", nullable=true)
     */
    private $timestamp;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=100, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=500, nullable=true)
     */
    private $message;


}

