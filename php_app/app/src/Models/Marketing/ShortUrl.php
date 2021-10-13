<?php
/**
 * Created by jamieaitken on 15/04/2018 at 11:05
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Marketing;

use Doctrine\ORM\Mapping as ORM;

/**
 * ShortUrl
 *
 * @ORM\Table(name="shortened_url")
 * @ORM\Entity
 */
class ShortUrl
{

    public function __construct(string $url, string $shortVersion, string $user)
    {
        $this->longUrl   = $url;
        $this->shortUrl  = $shortVersion;
        $this->createdBy = $user;
        $this->createdAt = new \DateTime();
    }

    /**
     * @var string
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="longUrl", type="string")
     */
    private $longUrl;

    /**
     * @var string
     * @ORM\Column(name="shortUrl", type="string")
     */
    private $shortUrl;

    /**
     * @var string
     * @ORM\Column(name="createdBy", type="string")
     */
    private $createdBy;

    /**
     * @var \DateTime
     * @ORM\Column(name="createdAt", type="datetime")
     */
    private $createdAt;

    /**
     * @return array
     */

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

    public static function generate()
    {
        $newDate = new \DateTime();

        $shaString = sha1($newDate->getTimestamp());

        $startIndex = rand(0, strlen($shaString) - 6);

        return substr($shaString, $startIndex, 6);
    }
}