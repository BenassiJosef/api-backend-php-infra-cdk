<?php

namespace DoctrineProxies\__CG__\App\Models\Radius;

/**
 * DO NOT EDIT THIS FILE - IT WAS CREATED BY DOCTRINE'S PROXY GENERATOR
 */
class RadiusAccounting extends \App\Models\Radius\RadiusAccounting implements \Doctrine\ORM\Proxy\Proxy
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
            return ['__isInitialized__', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'radacctid', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctSessionId', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctUniqueId', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'userName', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'groupName', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'realm', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'nasIpAddress', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'nasPortId', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'nasPortType', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctStartTime', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctUpdateTime', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctStopTime', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctSessionTime', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctAuthentic', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'connectInfoStart', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'connectInfoStop', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctInputOctets', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctOutputOctets', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'calledStationId', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'callingStationId', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctTerminateCause', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'seviceType', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'framedProtocol', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'framedIpAddress'];
        }

        return ['__isInitialized__', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'radacctid', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctSessionId', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctUniqueId', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'userName', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'groupName', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'realm', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'nasIpAddress', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'nasPortId', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'nasPortType', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctStartTime', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctUpdateTime', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctStopTime', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctSessionTime', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctAuthentic', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'connectInfoStart', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'connectInfoStop', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctInputOctets', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctOutputOctets', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'calledStationId', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'callingStationId', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'acctTerminateCause', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'seviceType', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'framedProtocol', '' . "\0" . 'App\\Models\\Radius\\RadiusAccounting' . "\0" . 'framedIpAddress'];
    }

    /**
     * 
     */
    public function __wakeup()
    {
        if ( ! $this->__isInitialized__) {
            $this->__initializer__ = function (RadiusAccounting $proxy) {
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

}
