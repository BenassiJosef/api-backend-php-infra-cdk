<?php

/**
 * Created by jamieaitken on 06/02/2018 at 16:31
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Position;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * LocationPosition
 *
 * @ORM\Table(name="network_settings_location")
 * @ORM\Entity
 */
class LocationPosition implements JsonSerializable
{

    public function __construct(
        float $latitude,
        float $longitude,
        string $formattedAddress,
        string $postCode,
        string $route,
        string $postalTown,
        string $administrativeAreaLevel2,
        string $administrativeAreaLevel1,
        string $country
    ) {
        $this->latitude                 = $latitude;
        $this->longitude                = $longitude;
        $this->formattedAddress         = $formattedAddress;
        $this->postCode                 = $postCode;
        $this->route                    = $route;
        $this->postalTown               = $postalTown;
        $this->administrativeAreaLevel2 = $administrativeAreaLevel2;
        $this->administrativeAreaLevel1 = $administrativeAreaLevel1;
        $this->country                  = $country;
        $this->updatedAt                = new \DateTime();
    }

    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=36, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="postCode", type="string")
     */
    private $postCode;

    /**
     * @var string
     * @ORM\Column(name="route", type="string")
     */
    private $route;

    /**
     * @var string
     * @ORM\Column(name="postalTown", type="string")
     */
    private $postalTown;

    /**
     * @var string
     * @ORM\Column(name="administrativeAreaLevel2", type="string")
     */
    private $administrativeAreaLevel2;

    /**
     * @var string
     * @ORM\Column(name="administrativeAreaLevel1", type="string")
     */
    private $administrativeAreaLevel1;

    /**
     * @var string
     * @ORM\Column(name="country", type="string")
     */
    private $country;

    /**
     * @var float
     * @ORM\Column(name="latitude", type="float")
     */
    private $latitude;

    /**
     * @var float
     * @ORM\Column(name="longitude", type="float")
     */
    private $longitude;

    /**
     * @var string
     * @ORM\Column(name="formattedAddress", type="string")
     */
    private $formattedAddress;

    /**
     * @var string
     * @ORM\Column(name="googlePlaceId", type="string")
     */
    private $googlePlaceId;

    /**
     * @var string
     * @ORM\Column(name="tripadvisorId", type="string")
     */
    private $tripadvisorId;

    /**
     * @var string
     * @ORM\Column(name="tripadvisorWriteReviewLink", type="string")
     */
    private $tripadvisorWriteReviewLink;

    /**
     * @var \DateTime
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

    public static function defaultLat()
    {
        return 55.976583;
    }

    public static function defaultLng()
    {
        return -3.1681647;
    }

    public static function defaultFormattedAddress()
    {
        return '1 Constitution St, Edinburgh EH6 7BG, UK';
    }

    public static function defaultPostCode()
    {
        return 'EH6 7BG';
    }

    public static function defaultRoute()
    {
        return 'Constitution Street';
    }

    public static function defaultPostalTown()
    {
        return 'Edinburgh';
    }

    public static function defaultAreaLevel2()
    {
        return 'Edinburgh';
    }

    public static function defaultAreaLevel1()
    {
        return 'Scotland';
    }

    public static function defaultCountry()
    {
        return 'United Kingdom';
    }

    public function partial()
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    public function jsonSerialize()
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'formatted_address' => $this->formattedAddress,
            'post_code' => $this->postCode,
            'route' => $this->route,
            'postal_town' => $this->postalTown,
            'administrative_area_level2' => $this->administrativeAreaLevel2,
            'administrative_area_level1' => $this->administrativeAreaLevel1,
            'country' => $this->country,
            'google_place_id' => $this->googlePlaceId,
            'tripadvisor_id' => $this->tripadvisorId,
            'tripadvisor_write_review_link' => $this->tripadvisorWriteReviewLink
        ];
    }

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
