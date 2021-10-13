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
 * FacebookOauth
 *
 * @ORM\Table(name="facebook_oauth")
 * @ORM\Entity
 */
class FacebookOauth
{

    /**
     * @var integer
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="uid", type="string")
     */
    private $uid;

    /**
     * @var string
     * @ORM\Column(name="organization_id", type="string")
     */
    private $orgId;

    /**
     * @var string
     * @ORM\Column(name="access_token", type="string")
     */
    private $accessToken;

    /**
     * @var \DateTime
     * @ORM\Column(name="expires_at", type="datetime")
     */
    private $expiresAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="issued_at", type="datetime")
     */
    private $issuedAt;

    /**
     * @var string
     * @ORM\Column(name="app_name", type="string")
     */
    private $appName;

    /**
     * @var string
     * @ORM\Column(name="account_alias", type="string")
     */
    private $accountAlias;

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