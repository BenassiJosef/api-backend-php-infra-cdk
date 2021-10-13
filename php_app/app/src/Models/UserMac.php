<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * UserMac
 *
 * @ORM\Table(name="user_mac")
 * @ORM\Entity
 */
class UserMac
{
    /**
     * @var string
     *
     * @ORM\Column(name="mac", type="string", length=17, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $mac = '';


}

