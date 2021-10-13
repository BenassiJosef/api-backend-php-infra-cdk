<?php

namespace App\Models;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * BouncedEmails
 *
 * @ORM\Table(name="bounced_emails")
 * @ORM\Entity
 */
class BouncedEmails
{
    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", nullable=false)
     * @ORM\Id
     */
    private $email;


    /**
     * @var DateTime
     *
     * @ORM\Column(name="bounced_at", type="datetime", nullable=false)
     */
    private $bouncedAt;

    public function __construct(string $email)
    {
        $this->email = $email;
        $this->bouncedAt = new DateTime();
    }
}
