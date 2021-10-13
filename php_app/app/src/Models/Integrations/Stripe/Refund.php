<?php
namespace App\Models\Integrations\Stripe;
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 30/05/2017
 * Time: 11:36
 */
use Doctrine\ORM\Mapping as ORM;

/**
 * Plans
 *
 * @ORM\Table(name="nearly_refunds")
 * @ORM\Entity
 */
class Refund
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="string")
     * @var string $id
     */
    private $id;

    /**
     * @ORM\Column(name="comment", type="string")
     * @var string $id
     */
    private $comment;

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