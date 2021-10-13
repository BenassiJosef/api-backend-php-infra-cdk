<?php
/**
 * Created by jamieaitken on 07/05/2018 at 09:42
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Nearly;

use Doctrine\ORM\Mapping as ORM;

/**
 * Email Domain Valid
 *
 * @ORM\Table(name="email_domains")
 * @ORM\Entity
 */

class EmailDomainValid
{

    public function __construct(string $domain, bool $valid)
    {
        $this->domainName = $domain;
        $this->isValid    = $valid;
    }


    /**
     * @var integer
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="domainName", type="string")
     */
    private $domainName;

    /**
     * @var boolean
     * @ORM\Column(name="isValid", type="boolean")
     */
    private $isValid;

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
}