<?php

namespace DoctrineProxies\__CG__\App\Models;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class MarketingCampaigns extends \App\Models\MarketingCampaigns implements \Doctrine\ORM\Proxy\Proxy
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
            return ['__isInitialized__', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'id', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'active', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'hasLimit', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'created', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'edited', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'eventId', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'filterId', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'messageId', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'message', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'template', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'name', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'admin', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'organizationId', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'limit', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'spendPerHead', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'deleted', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'templateId', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'automation', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'lastSentAt', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'report'];
        }

        return ['__isInitialized__', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'id', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'active', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'hasLimit', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'created', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'edited', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'eventId', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'filterId', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'messageId', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'message', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'template', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'name', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'admin', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'organizationId', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'limit', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'spendPerHead', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'deleted', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'templateId', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'automation', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'lastSentAt', '' . "\0" . 'App\\Models\\MarketingCampaigns' . "\0" . 'report'];
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (MarketingCampaigns $proxy) {
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
    public function getArrayCopy()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getArrayCopy', []);

        return parent::getArrayCopy();
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        if ($this->__isInitialized__ === false) {
            return  parent::getId();
        }


        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getId', []);

        return parent::getId();
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isActive', []);

        return parent::isActive();
    }

    /**
     * {@inheritDoc}
     */
    public function isHasLimit(): bool
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'isHasLimit', []);

        return parent::isHasLimit();
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
    public function getEventId(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getEventId', []);

        return parent::getEventId();
    }

    /**
     * {@inheritDoc}
     */
    public function getFilterId(): ?string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getFilterId', []);

        return parent::getFilterId();
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getName', []);

        return parent::getName();
    }

    /**
     * {@inheritDoc}
     */
    public function getAdmin(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAdmin', []);

        return parent::getAdmin();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrganizationId(): \Ramsey\Uuid\UuidInterface
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getOrganizationId', []);

        return parent::getOrganizationId();
    }

    /**
     * {@inheritDoc}
     */
    public function getLimit(): ?int
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLimit', []);

        return parent::getLimit();
    }

    /**
     * {@inheritDoc}
     */
    public function getSpendPerHead(): int
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getSpendPerHead', []);

        return parent::getSpendPerHead();
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
    public function getTemplateId(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getTemplateId', []);

        return parent::getTemplateId();
    }

    /**
     * {@inheritDoc}
     */
    public function getAutomation(): string
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getAutomation', []);

        return parent::getAutomation();
    }

    /**
     * {@inheritDoc}
     */
    public function sent()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'sent', []);

        return parent::sent();
    }

    /**
     * {@inheritDoc}
     */
    public function getReport(): \App\Package\Marketing\MarketingReportRow
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getReport', []);

        return parent::getReport();
    }

    /**
     * {@inheritDoc}
     */
    public function setReport(\App\Package\Marketing\MarketingReportRow $report)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setReport', [$report]);

        return parent::setReport($report);
    }

    /**
     * {@inheritDoc}
     */
    public function getLastSentAt(): ?\DateTime
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getLastSentAt', []);

        return parent::getLastSentAt();
    }

    /**
     * {@inheritDoc}
     */
    public function getMessage(): ?\App\Models\MarketingMessages
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getMessage', []);

        return parent::getMessage();
    }

    /**
     * {@inheritDoc}
     */
    public function getTemplate(): ?\App\Models\Marketing\TemplateSettings
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'getTemplate', []);

        return parent::getTemplate();
    }

    /**
     * {@inheritDoc}
     */
    public function setMessage(\App\Models\MarketingMessages $message)
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'setMessage', [$message]);

        return parent::setMessage($message);
    }

    /**
     * {@inheritDoc}
     */
    public function touch()
    {

        $this->__initializer__ && $this->__initializer__->__invoke($this, 'touch', []);

        return parent::touch();
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
