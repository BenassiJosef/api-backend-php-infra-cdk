<?php
/**
 * Created by jamieaitken on 15/03/2018 at 16:41
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Members;

use Doctrine\ORM\Mapping as ORM;

/**
 * StandalonePartnerBranding
 *
 * @ORM\Table(name="standalone_partner_branding")
 * @ORM\Entity
 */
class StandalonePartnerBranding
{


    public static $mutableKeys = [
        'brandName',
        'company',
        'domain',
        'name',
        'phoneNo',
        'policy',
        'terms',
        'website',
        'support'
    ];

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(name="id", type="string")
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="brandName", type="string")
     */
    private $brandName;

    /**
     * @var string
     * @ORM\Column(name="company", type="string")
     */
    private $company;

    /**
     * @var string
     * @ORM\Column(name="domain", type="string")
     */
    private $domain;

    /**
     * @var string
     * @ORM\Column(name="name", type="string")
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="phoneNo", type="string")
     */
    private $phoneNo;

    /**
     * @var string
     * @ORM\Column(name="policy", type="string")
     */
    private $policy;

    /**
     * @var string
     * @ORM\Column(name="terms", type="string")
     */
    private $terms;

    /**
     * @var string
     * @ORM\Column(name="website", type="string")
     */
    private $website;

    /**
     * @var string
     * @ORM\Column(name="support", type="string")
     */
    private $support;

    /**
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