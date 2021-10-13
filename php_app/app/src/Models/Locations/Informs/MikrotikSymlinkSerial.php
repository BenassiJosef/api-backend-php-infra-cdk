<?php

/**
 * Created by jamieaitken on 29/01/2019 at 11:39
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Informs;

use App\Controllers\Locations\_LocationCreationController;
use App\Utils\Strings;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="mikrotik_symlink_serials")
 * @ORM\Entity
 */
class MikrotikSymlinkSerial
{

    public function __construct(string $physicalSerial)
    {
        $this->physicalSerial = $physicalSerial;
        $this->virtualSerial  = strtoupper(Strings::random(12));
    }

    /**
     * @var string
     *
     * @ORM\Column(name="physicalSerial", type="string", nullable=false)
     * @ORM\Id
     */
    private $physicalSerial;

    /**
     * @var string
     *
     * @ORM\Column(name="virtualSerial", type="string")
     *
     */
    private $virtualSerial;

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
