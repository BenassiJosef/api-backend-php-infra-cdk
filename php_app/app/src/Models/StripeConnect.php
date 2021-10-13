<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 03/01/2017
 * Time: 12:52
 */

namespace App\Models;

use App\Models\Organization;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * StripeConnect
 *
 * @ORM\Table(name="stripeConnect")
 * @ORM\Entity
 */
class StripeConnect
{
    /**
     * StripeConnect constructor.
     * @param \App\Models\Organization $organization
     * @param string $tokenType
     * @param string $stripeUserId
     * @param string $stripePublishableKey
     * @param string $scope
     * @param bool $liveMode
     * @param string $refreshToken
     * @param string $accessToken
     */
    public function __construct(
        Organization $organization,
        $tokenType,
        $stripeUserId,
        $stripePublishableKey,
        $scope,
        $liveMode,
        $refreshToken,
        $accessToken
    ) {

        $this->token_type             = $tokenType;
        $this->stripe_user_id         = $stripeUserId;
        $this->stripe_publishable_key = $stripePublishableKey;
        $this->scope                  = $scope;
        $this->livemode               = $liveMode;
        $this->refresh_token          = $refreshToken;
        $this->access_token           = $accessToken;
        $this->uid                    = $organization->getOwnerId()->toString();
        $this->organizationId         = $organization->getId();
        $this->organization = $organization;
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
     * @ORM\Column(name="uid", type="string", length=38, nullable=true)
     */
    private $uid;

    /**
     * @var string
     * @ORM\Column(name="token_type", type="string", length=10, nullable=true)
     */
    private $token_type;

    /**
     * @var string
     * @ORM\Column(name="stripe_publishable_key", type="string", length=100, nullable=true)
     */
    private $stripe_publishable_key;

    /**
     * @var string
     * @ORM\Column(name="scope", type="string", length=12, nullable=true)
     */
    private $scope;

    /**
     * @var boolean
     * @ORM\Column(name="livemode", type="boolean", nullable=true)
     */
    private $livemode = false;

    /**
     * @var string
     * @ORM\Column(name="stripe_user_id", type="string", length=100, nullable=true)
     */
    private $stripe_user_id;

    /**
     * @var string
     * @ORM\Column(name="refresh_token", type="string", length=100, nullable=true)
     */
    private $refresh_token;

    /**
     * @var string
     * @ORM\Column(name="access_token", type="string", length=100, nullable=true)
     */
    private $access_token;

    /**
     * @var string
     * @ORM\Column(name="account_display_name", type="string", length=64)
     */

    private $display_name;

    /**
     * @var bool
     * @ORM\Column(name="isDeleted", type="boolean")
     */

    private $isDeleted = false;

    /**
     * @var UuidInterface
     *
     * @ORM\Column(name="organization_id", type="uuid", length=36, nullable=true)
     */
    private $organizationId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Models\Organization", cascade={"persist"})
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", nullable=false)
     * @var Organization $organization
     */
    private $organization;

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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * @return string
     */
    public function getTokenType(): string
    {
        return $this->token_type;
    }

    /**
     * @return string
     */
    public function getStripePublishableKey(): string
    {
        return $this->stripe_publishable_key;
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * @return bool
     */
    public function isLivemode(): bool
    {
        return $this->livemode;
    }

    /**
     * @return string
     */
    public function getStripeUserId(): string
    {
        return $this->stripe_user_id;
    }

    /**
     * @return string
     */
    public function getRefreshToken(): string
    {
        return $this->refresh_token;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->access_token;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->display_name;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    /**
     * @return UuidInterface
     */
    public function getOrganizationId(): UuidInterface
    {
        return $this->organizationId;
    }

    /**
     * @return Organization
     */
    public function getOrganization(): Organization
    {
        return $this->organization;
    }

}