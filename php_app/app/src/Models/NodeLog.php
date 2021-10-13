<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * NodeLog
 *
 * @ORM\Table(name="node_log")
 * @ORM\Entity
 */
class NodeLog
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
     * @var integer
     *
     * @ORM\Column(name="node_id", type="integer", nullable=true)
     */
    private $nodeId;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=12, nullable=true)
     */
    private $serial;


}

