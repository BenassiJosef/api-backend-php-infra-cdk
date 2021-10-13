<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * Generic
 *
 * @ORM\Table(name="file_uploads")
 * @ORM\Entity
 */
class FileUploads
{

    public function __construct($kind, $path, $filename, $url)
    {
        $this->kind     = $kind;
        $this->path     = $path;
        $this->filename = $filename;
        $this->url      = $url;
        $this->deleted  = false;
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="kind", type="string", nullable=true)
     */
    private $kind;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", nullable=true)
     */

    protected $path;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", nullable=true)
     */

    private $filename;

    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", nullable=true)
     */

    private $url;

    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean", nullable=true)
     */

    private $deleted;

    /**
     * Get array copy of object
     *
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

