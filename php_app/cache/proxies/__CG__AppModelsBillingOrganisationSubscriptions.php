<?php

namespace DoctrineProxies\__CG__\App\Models\Billing\Organisation;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class Subscriptions extends \App\Models\Billing\Organisation\Subscriptions implements \Doctrine\ORM\Proxy\Proxy
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
     * 
     * @return array
     */
    public function __sleep()
    {
        if ($this->__isInitialized__) {
            return ['__isInitialized__', 'freeAddons', 'starterAddons', 'growthAddons', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'organizationId', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'organization', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'subscriptionId', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'addons', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'venues', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'contacts', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'plan', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'currency', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'status', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'annual', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'createdAt', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'legacy'];
        }

        return ['__isInitialized__', 'freeAddons', 'starterAddons', 'growthAddons', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'organizationId', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'organization', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'subscriptionId', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'addons', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'venues', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'contacts', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'plan', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'currency', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'status', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'annual', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'createdAt', '' . "\0" . 'App\\Models\\Billing\\Organisation\\Subscriptions' . "\0" . 'legacy'];
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (Subscriptions $proxy) {
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
    public function getIncludedSmsCredits()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getIncludedSmsCredits', []);

        return parent::getIncludedSmsCredits();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrganisationId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOrganisationId', []);

        return parent::getOrganisationId();
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscriptionId()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSubscriptionId', []);

        return parent::getSubscriptionId();
    }

    /**
     * {@inheritDoc}
     */
    public function setSubscriptionId(string $subscriptionId)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setSubscriptionId', [$subscriptionId]);

        return parent::setSubscriptionId($subscriptionId);
    }

    /**
     * {@inheritDoc}
     */
    public function chargeBeeAddons()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'chargeBeeAddons', []);

        return parent::chargeBeeAddons();
    }

    /**
     * {@inheritDoc}
     */
    public function hasAddon(...$addonsRequest): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'hasAddon', [$addonsRequest]);

        return parent::hasAddon(...$addonsRequest);
    }

    /**
     * {@inheritDoc}
     */
    public function getAddons()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAddons', []);

        return parent::getAddons();
    }

    /**
     * {@inheritDoc}
     */
    public function formatAddons(array $addons)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'formatAddons', [$addons]);

        return parent::formatAddons($addons);
    }

    /**
     * {@inheritDoc}
     */
    public function setAddons(array $addons)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAddons', [$addons]);

        return parent::setAddons($addons);
    }

    /**
     * {@inheritDoc}
     */
    public function getPlan()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getPlan', []);

        return parent::getPlan();
    }

    /**
     * {@inheritDoc}
     */
    public function setPlan(string $plan)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setPlan', [$plan]);

        return parent::setPlan($plan);
    }

    /**
     * {@inheritDoc}
     */
    public function getContacts()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getContacts', []);

        return parent::getContacts();
    }

    /**
     * {@inheritDoc}
     */
    public function setContacts(int $contacts)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setContacts', [$contacts]);

        return parent::setContacts($contacts);
    }

    /**
     * {@inheritDoc}
     */
    public function getChargeBeeVenues()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getChargeBeeVenues', []);

        return parent::getChargeBeeVenues();
    }

    /**
     * {@inheritDoc}
     */
    public function getVenues()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getVenues', []);

        return parent::getVenues();
    }

    /**
     * {@inheritDoc}
     */
    public function setVenues(int $venues)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setVenues', [$venues]);

        return parent::setVenues($venues);
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrency()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getCurrency', []);

        return parent::getCurrency();
    }

    /**
     * {@inheritDoc}
     */
    public function setCurrency(string $currency)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setCurrency', [$currency]);

        return parent::setCurrency($currency);
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getStatus', []);

        return parent::getStatus();
    }

    /**
     * {@inheritDoc}
     */
    public function setStatus(string $status)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setStatus', [$status]);

        return parent::setStatus($status);
    }

    /**
     * {@inheritDoc}
     */
    public function getAnnual()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAnnual', []);

        return parent::getAnnual();
    }

    /**
     * {@inheritDoc}
     */
    public function setAnnual(bool $annual)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setAnnual', [$annual]);

        return parent::setAnnual($annual);
    }

    /**
     * {@inheritDoc}
     */
    public function isSubscriptionValid(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isSubscriptionValid', []);

        return parent::isSubscriptionValid();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrganisation(): \App\Models\Organization
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOrganisation', []);

        return parent::getOrganisation();
    }

    /**
     * {@inheritDoc}
     */
    public function isLegacy(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isLegacy', []);

        return parent::isLegacy();
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'jsonSerialize', []);

        return parent::jsonSerialize();
    }

}
