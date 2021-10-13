<?php


namespace App\Models;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * Class UidToUUID
 *
 * @ORM\Table(name="uid_to_uuid_map")
 * @ORM\Entity
 * @package App\Models
 */
class UidToUUID
{
    /**
     * @ORM\Id
     * @ORM\Column(name="wrong", type="string")
     * @var string $wrong
     */
    private $wrong;

    /**
     * @ORM\Column(name="correct", type="uuid")
     * @var UuidInterface $correct
     */
    private $correct;

    /**
     * @return string
     */
    public function getWrong(): string
    {
        return $this->wrong;
    }

    /**
     * @return UuidInterface
     */
    public function getCorrect(): UuidInterface
    {
        return $this->correct;
    }
}