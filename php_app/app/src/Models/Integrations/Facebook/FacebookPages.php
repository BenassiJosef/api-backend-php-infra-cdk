<?php

/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 18/08/2017
 * Time: 12:00
 */

namespace App\Models\Integrations\Facebook;

use Doctrine\ORM\Mapping as ORM;

/**
 * FacebookPages
 *
 * @ORM\Table(name="facebook_pages")
 * @ORM\Entity
 */
class FacebookPages
{

    /**
     * @var integer
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     * @ORM\Column(name="facebook_oauth_id", type="integer")
     */
    private $facebookOauthId;

    /**
     * @var string
     * @ORM\Column(name="access_token", type="string")
     */
    private $accessToken;

    /**
     * @var string
     * @ORM\Column(name="page_id", type="string")
     */
    private $pageId;

    /**
     * @var string
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="category", type="string")
     */
    private $category;

    /**
     * @var string
     * @ORM\Column(name="reviewId", type="string")
     */
    private $reviewId;


    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

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
