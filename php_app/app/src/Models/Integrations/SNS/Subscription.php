<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 19/05/2017
 * Time: 15:08
 */

namespace App\Models\Integrations\SNS;

use Doctrine\ORM\Mapping as ORM;

class Subscription
{
    public function __construct(string $arn)
    {
        $this->subscriptionArn = $arn;
        $this->active          = true;
        $this->createdAt       = new \DateTime();
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
     * @ORM\Column(name="subscriptionArn", type="string", length=64, nullable=false)
     */
    private $subscriptionArn;

    /**
     * @var boolean
     * @ORM\Column(name="active", type="active")
     */
    private $active;

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