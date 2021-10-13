<?php
namespace App\Models\Members\Groups;
use Doctrine\ORM\Mapping as ORM;
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 03/04/2017
 * Time: 17:06
 */
class Group
{
    public function __construct(string $name, string $leader)
    {
        $this->name      = $name;
        $this->leader    = $leader;
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
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="leader", type="string")
     */
    private $leader;

    /**
     * @var /DateTime
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