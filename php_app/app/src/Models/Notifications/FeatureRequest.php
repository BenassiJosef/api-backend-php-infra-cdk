<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/09/2017
 * Time: 15:09
 */

namespace App\Models\Notifications;

use Doctrine\ORM\Mapping as ORM;

/**
 * Generic
 *
 * @ORM\Table(name="feature_request", indexes={
 *     @ORM\Index(name="buildNumber", columns={"buildNumber"}),
 *     @ORM\Index(name="status", columns={"status"}),
 *     @ORM\Index(name="timestamp", columns={"requestedAt"})
 * })
 * @ORM\Entity
 */
class FeatureRequest
{

    static $mutableKeys = [
        'name',
        'description',
        'completedAt',
        'buildNumber',
        'status',
        'category'
    ];

    static $allowedStatus = [
        'Submitted',
        'Progress',
        'Planning',
        'Completed'
    ];

    static $allowedCategories = [
        'Marketing',
        'Connect',
        'Splash Screen',
        'Reports',
        'Integration',
        'Hardware'
    ];

    public function __construct(
        string $name,
        string $description,
        string $buildNumber,
        string $status,
        string $category
    ) {
        $now               = new \DateTime();
        $this->name        = $name;
        $this->description = $description;
        $this->requestedAt = $now->getTimestamp();
        $this->buildNumber = $buildNumber;
        $this->status      = $status;
        $this->category    = $category;
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
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="description", type="string", nullable=false)
     */
    private $description;

    /**
     * @var integer
     * @ORM\Column(name="requestedAt", type="integer", nullable=false)
     */
    private $requestedAt;

    /**
     * @var integer
     * @ORM\Column(name="completedAt", type="integer")
     */
    private $completedAt;

    /**
     * @var string
     * @ORM\Column(name="buildNumber", type="string")
     */
    private $buildNumber;

    /**
     * @var string
     * @ORM\Column(name="status", type="string")
     */
    private $status;

    /**
     * @var string
     * @ORM\Column(name="category", type="string")
     */
    private $category;

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