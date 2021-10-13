<?php



use Doctrine\ORM\Mapping as ORM;

/**
 * OauthRefreshTokens
 *
 * @ORM\Table(name="oauth_refresh_tokens")
 * @ORM\Entity
 */
class OauthRefreshTokens
{
    /**
     * @var string
     *
     * @ORM\Column(name="refresh_token", type="string", length=40, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $refreshToken;

    /**
     * @var string
     *
     * @ORM\Column(name="client_id", type="string", length=80, nullable=false)
     */
    private $clientId;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="string", length=255, nullable=true)
     */
    private $userId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expires", type="datetime", nullable=false)
     */
    private $expires = 'CURRENT_TIMESTAMP';

    /**
     * @var string
     *
     * @ORM\Column(name="scope", type="string", length=2000, nullable=true)
     */
    private $scope;


}

