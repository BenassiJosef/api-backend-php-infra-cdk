<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;

/**
 * OauthClients
 *
 * @ORM\Table(name="oauth_clients")
 * @ORM\Entity
 */
class OauthClients
{

    /**
     * @var array
     */
    public static $endUserClients = [
        'stampede.ai.my',
        'stampede.ai.my_stage',
        'stampede.ai.loyalty',
        'stampede.ai.loyalty_stage',
        'my.app.local.stampede.ai',
        'my.app.production.stampede.ai',
        'my.app.stampede.ai',
    ];

    /**
     * @var array
     */
    public static $loyaltyClients = [

        'stampede.ai.loyalty',
        'stampede.ai.loyalty_stage'
    ];

    /**
     * @var string
     *
     * @ORM\Column(name="client_id", type="string", length=80, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $clientId;

    /**
     * @var string
     *
     * @ORM\Column(name="client_secret", type="string", length=80, nullable=true)
     */
    private $clientSecret;

    /**
     * @var string
     *
     * @ORM\Column(name="redirect_uri", type="string", length=2000, nullable=false)
     */
    private $redirectUri;

    /**
     * @var string
     *
     * @ORM\Column(name="grant_types", type="string", length=80, nullable=true)
     */
    private $grantTypes;

    /**
     * @var string
     *
     * @ORM\Column(name="scope", type="string", length=100, nullable=true)
     */
    private $scope;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", length=80, nullable=true)
     */
    private $userId;

    public function isLoyaltyClient()
    {
        return in_array($this->clientId, self::$loyaltyClients);
    }

    public function isEndUserClient()
    {
        return in_array($this->clientId, self::$endUserClients);
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function getGrantTypes()
    {
        return $this->grantTypes;
    }

    public function setClientId(string $clientId)
    {
        $this->clientId = $clientId;
    }
}
