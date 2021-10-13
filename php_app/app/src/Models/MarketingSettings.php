<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * MarketingSettings
 *
 * @ORM\Table(name="marketing_settings")
 * @ORM\Entity
 */
class MarketingSettings
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}

