<?php

namespace DoctrineProxies\__CG__\App\Models;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class OauthUser extends \App\Models\OauthUser implements \Doctrine\ORM\Proxy\Proxy
{
    /**
     * @var \Closure the callback responsible for loading properties in the proxy object. This callback is called with
     *      three parameters, being respectively the proxy object to be initialized, the method that triggered the
     *      initialization process and an array of ordered parameters that were passed to that method.
     *
     * @see \Doctrine\Common\Proxy\Proxy::__setInitializer
     */
    public $__initializer__;

    /**
     * @var \Closure the callback responsible of loading properties that need to be copied in the cloned object
     *
     * @see \Doctrine\Common\Proxy\Proxy::__setCloner
     */
    public $__cloner__;

    /**
     * @var boolean flag indicating if this object was already initialized
     *
     * @see \Doctrine\Persistence\Proxy::__isInitialized
     */
    public $__isInitialized__ = false;

    /**
     * @var array<string, null> properties to be lazy loaded, indexed by property name
     */
    public static $lazyPropertiesNames = array (
);

    /**
     * @var array<string, mixed> default values of properties to be lazy loaded, with keys being the property names
     *
     * @see \Doctrine\Common\Proxy\Proxy::__getLazyProperties
     */
    public static $lazyPropertiesDefaults = array (
);



    public function __construct(?\Closure $initializer = null, ?\Closure $cloner = null)
    {

        $this->__initializer__ = $initializer;
        $this->__cloner__      = $cloner;
    }

    /**
     * {@inheritDoc}
     * @param string $name
     */
    public function __get($name)
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__get', [$name]);
        return parent::__get($name);
    }

    /**
     * {@inheritDoc}
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__set', [$name, $value]);

        return parent::__set($name, $value);
    }



    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return ['__isInitialized__', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'uid', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'admin', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'reseller', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'email', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'password', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'company', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'first', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'last', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'inChargeBee', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'stripe_id', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'role', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'country', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'locationAccess', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'created', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'edited', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'deleted', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'access', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'organisationAccess'];
        }

        return ['__isInitialized__', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'uid', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'admin', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'reseller', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'email', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'password', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'company', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'first', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'last', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'inChargeBee', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'stripe_id', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'role', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'country', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'locationAccess', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'created', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'edited', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'deleted', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'access', '' . "\0" . 'App\\Models\\OauthUser' . "\0" . 'organisationAccess'];
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (OauthUser $proxy) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);

                $existingProperties = get_object_vars($proxy);

                foreach ($proxy::$lazyPropertiesDefaults as $property => $defaultValue) {
                    if ( ! array_key_exists($property, $existingProperties)) {
                        $proxy->$property = $defaultValue;
                    }
                }
            };

        }
    }

    /**
     * 
     */
    public function __clone()
    {
        $this->__cloner__ && $this->__cloner__->__invoke($this, '__clone', []);
    }

    /**
     * Forces initialization of the proxy
     */
    public function __load()
    {
        $this->__initializer__ && $this->__initializer__->__invoke($this, '__load', []);
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitialized($initialized)
    {
        $this->__isInitialized__ = $initialized;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setInitializer(\Closure $initializer = null)
    {
        $this->__initializer__ = $initializer;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __getInitializer()
    {
        return $this->__initializer__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     */
    public function __setCloner(\Closure $cloner = null)
    {
        $this->__cloner__ = $cloner;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific cloning logic
     */
    public function __getCloner()
    {
        return $this->__cloner__;
    }

    /**
     * {@inheritDoc}
     * @internal generated method: use only when explicitly handling proxy specific loading logic
     * @deprecated no longer in use - generated code now relies on internal components rather than generated public API
     * @static
     */
    public function __getLazyProperties()
    {
        return self::$lazyPropertiesDefaults;
    }

    
    /**
     * {@inheritDoc}
     */
    public function getUser(): \App\Models\OauthUser
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUser', []);

        return parent::getUser();
    }

    /**
     * {@inheritDoc}
     */
    public function getUserId(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUserId', []);

        return parent::getUserId();
    }

    /**
     * {@inheritDoc}
     */
    public function getUid(): string
    {
        if ($this->__isInitialized__ === false) {
            return  parent::getUid();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getUid', []);

        return parent::getUid();
    }

    /**
     * {@inheritDoc}
     */
    public function getAdmin(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAdmin', []);

        return parent::getAdmin();
    }

    /**
     * {@inheritDoc}
     */
    public function getReseller(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getReseller', []);

        return parent::getReseller();
    }

    /**
     * {@inheritDoc}
     */
    public function getLocationAccess()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLocationAccess', []);

        return parent::getLocationAccess();
    }

    /**
     * {@inheritDoc}
     */
    public function getEmail(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEmail', []);

        return parent::getEmail();
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPassword', []);

        return parent::getPassword();
    }

    /**
     * {@inheritDoc}
     */
    public function getCompany(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCompany', []);

        return parent::getCompany();
    }

    /**
     * {@inheritDoc}
     */
    public function getFirst(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFirst', []);

        return parent::getFirst();
    }

    /**
     * {@inheritDoc}
     */
    public function getLast(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLast', []);

        return parent::getLast();
    }

    /**
     * {@inheritDoc}
     */
    public function isInChargeBee(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isInChargeBee', []);

        return parent::isInChargeBee();
    }

    /**
     * {@inheritDoc}
     */
    public function getStripeId(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getStripeId', []);

        return parent::getStripeId();
    }

    /**
     * {@inheritDoc}
     */
    public function getRole(): int
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getRole', []);

        return parent::getRole();
    }

    /**
     * {@inheritDoc}
     */
    public function getCountry(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCountry', []);

        return parent::getCountry();
    }

    /**
     * {@inheritDoc}
     */
    public function getCreated(): \DateTime
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCreated', []);

        return parent::getCreated();
    }

    /**
     * {@inheritDoc}
     */
    public function getEdited(): ?\DateTime
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEdited', []);

        return parent::getEdited();
    }

    /**
     * {@inheritDoc}
     */
    public function isDeleted(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isDeleted', []);

        return parent::isDeleted();
    }

    /**
     * {@inheritDoc}
     */
    public function fullName()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'fullName', []);

        return parent::fullName();
    }

    /**
     * {@inheritDoc}
     */
    public function getArrayCopy()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getArrayCopy', []);

        return parent::getArrayCopy();
    }

    /**
     * {@inheritDoc}
     */
    public function setAccess(array $serials)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAccess', [$serials]);

        return parent::setAccess($serials);
    }

    /**
     * {@inheritDoc}
     */
    public function getAccess(): array
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAccess', []);

        return parent::getAccess();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrganisationAccess()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOrganisationAccess', []);

        return parent::getOrganisationAccess();
    }

    /**
     * {@inheritDoc}
     */
    public function setOrganisationAccess(array $access)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setOrganisationAccess', [$access]);

        return parent::setOrganisationAccess($access);
    }

    /**
     * {@inheritDoc}
     */
    public function getFilteredAccess(): array
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFilteredAccess', []);

        return parent::getFilteredAccess();
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'jsonSerialize', []);

        return parent::jsonSerialize();
    }

    /**
     * {@inheritDoc}
     */
    public function setInChargeBee(bool $inChargeBee): void
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setInChargeBee', [$inChargeBee]);

        parent::setInChargeBee($inChargeBee);
    }

    /**
     * {@inheritDoc}
     */
    public function setAdmin(string $admin): \App\Models\OauthUser
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAdmin', [$admin]);

        return parent::setAdmin($admin);
    }

    /**
     * {@inheritDoc}
     */
    public function setReseller(string $reseller): \App\Models\OauthUser
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setReseller', [$reseller]);

        return parent::setReseller($reseller);
    }

    /**
     * {@inheritDoc}
     */
    public function setEmail(string $email): \App\Models\OauthUser
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setEmail', [$email]);

        return parent::setEmail($email);
    }

    /**
     * {@inheritDoc}
     */
    public function setPassword(string $password): \App\Models\OauthUser
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPassword', [$password]);

        return parent::setPassword($password);
    }

    /**
     * {@inheritDoc}
     */
    public function setCompany(string $company): \App\Models\OauthUser
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCompany', [$company]);

        return parent::setCompany($company);
    }

    /**
     * {@inheritDoc}
     */
    public function setFirst(string $first): \App\Models\OauthUser
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setFirst', [$first]);

        return parent::setFirst($first);
    }

    /**
     * {@inheritDoc}
     */
    public function setLast(string $last): \App\Models\OauthUser
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setLast', [$last]);

        return parent::setLast($last);
    }

    /**
     * {@inheritDoc}
     */
    public function setStripeId(string $stripe_id): \App\Models\OauthUser
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setStripeId', [$stripe_id]);

        return parent::setStripeId($stripe_id);
    }

    /**
     * {@inheritDoc}
     */
    public function setRole(int $role): \App\Models\OauthUser
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setRole', [$role]);

        return parent::setRole($role);
    }

    /**
     * {@inheritDoc}
     */
    public function setCountry(string $country): \App\Models\OauthUser
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCountry', [$country]);

        return parent::setCountry($country);
    }

    /**
     * {@inheritDoc}
     */
    public function setEdited(\DateTime $edited): \App\Models\OauthUser
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setEdited', [$edited]);

        return parent::setEdited($edited);
    }

}
