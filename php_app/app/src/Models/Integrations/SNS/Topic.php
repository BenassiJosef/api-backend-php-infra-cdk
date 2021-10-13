<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 20/05/2017
 * Time: 14:12
 */

namespace App\Models\Integrations\SNS;

use Doctrine\ORM\Mapping as ORM;

/**
 * Generic
 *
 * @ORM\Table(name="topics")
 * @ORM\Entity
 */
class Topic
{

    public function __construct(string $name, string $createdBy)
    {
        $this->name      = $name;
        $this->createdBy = $createdBy;
        $this->createdAt = new \DateTime();
    }


    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="topicName", type="string", nullable=false)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="createdBy", type="string", length=36)
     */
    private $createdBy;

    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

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