<?php

/**
 * Created by jamieaitken on 05/02/2018 at 16:06
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Locations\Branding;

use App\Utils\Formatters\JsonDeserializer;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use JsonSerializable;

/**
 * LocationBranding
 *
 * @ORM\Table(name="network_settings_branding")
 * @ORM\Entity
 */
class LocationBranding extends JsonDeserializer implements JsonSerializable
{

    public function __construct(
        string $background,
        bool $boxShadow,
        string $footer,
        bool $headerPadding,
        bool $headerTopRadius,
        string $headerColor,
        string $input,
        string $backgroundImage,
        string $headerImage,
        string $primary,
        bool $roundFormTopLeft,
        bool $roundFormTopRight,
        bool $roundFormBottomLeft,
        bool $roundFormBottomRight,
        string $textColor,
        bool $roundInputs,
        bool $hideFooter
    ) {
        $this->background           = $background;
        $this->boxShadow            = $boxShadow;
        $this->footer               = $footer;
        $this->headerLogoPadding    = $headerPadding;
        $this->headerTopRadius      = $headerTopRadius;
        $this->headerColor          = $headerColor;
        $this->input                = $input;
        $this->backgroundImage      = $backgroundImage;
        $this->headerImage          = $headerImage;
        $this->primary              = $primary;
        $this->roundFormTopLeft     = $roundFormTopLeft;
        $this->roundFormTopRight    = $roundFormTopRight;
        $this->roundFormBottomLeft  = $roundFormBottomLeft;
        $this->roundFormBottomRight = $roundFormBottomRight;
        $this->textColor            = $textColor;
        $this->roundInputs          = $roundInputs;
        $this->hideFooter           = $hideFooter;
        $this->updatedAt            = new \DateTime();
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
     * @ORM\Column(name="background", type="string")
     *
     */
    private $background;

    /**
     * @var boolean
     * @ORM\Column(name="boxShadow", type="boolean")
     *
     */
    private $boxShadow;

    /**
     * @var string
     * @ORM\Column(name="footer", type="string")
     *
     */
    private $footer;

    /**
     * @var boolean
     * @ORM\Column(name="headerLogoPadding", type="boolean")
     *
     */
    private $headerLogoPadding;

    /**
     * @var boolean
     * @ORM\Column(name="headerTopRadius", type="boolean")
     *
     */
    private $headerTopRadius;

    /**
     * @var string
     * @ORM\Column(name="headerColor", type="string")
     */
    private $headerColor;

    /**
     * @var string
     * @ORM\Column(name="input", type="string")
     *
     */
    private $input;

    /**
     * @var string
     * @ORM\Column(name="backgroundImage", type="string")
     *
     */
    private $backgroundImage;

    /**
     * @var string
     * @ORM\Column(name="headerImage", type="string")
     *
     */
    private $headerImage;

    /**
     * @var string
     * @ORM\Column(name="primaryColor", type="string")
     *
     */
    private $primary;

    /**
     * @var boolean
     * @ORM\Column(name="roundFormTopLeft", type="boolean")
     *
     */
    private $roundFormTopLeft;

    /**
     * @var boolean
     * @ORM\Column(name="roundFormTopRight", type="boolean")
     *
     */
    private $roundFormTopRight;

    /**
     * @var boolean
     * @ORM\Column(name="roundFormBottomLeft", type="boolean")
     *
     */
    private $roundFormBottomLeft;

    /**
     * @var boolean
     * @ORM\Column(name="roundFormBottomRight", type="boolean")
     *
     */
    private $roundFormBottomRight;

    /**
     * @var string
     * @ORM\Column(name="textColor", type="string")
     *
     */
    private $textColor;

    /**
     * @var boolean
     * @ORM\Column(name="roundInputs", type="boolean")
     *
     */
    private $roundInputs;

    /**
     * @var string
     * @ORM\Column(name="message", type="string")
     */
    private $message;

    /**
     * @var boolean
     * @ORM\Column(name="hideFooter", type="boolean")
     *
     */
    private $hideFooter;

    /**
     * @var string
     * @ORM\Column(name="interfaceColor", type="string")
     */
    private $interfaceColor;

    /**
     * @var string
     * @ORM\Column(name="customCSS", type="string")
     */
    private $customCss;

    /**
     * @var \DateTime
     * @ORM\Column(name="updatedAt", type="datetime")
     */
    private $updatedAt;

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

    public function getId(): string
    {
        return $this->id;
    }

    public static function defaultBackground()
    {
        return 'rgb(255, 255, 255)';
    }

    public static function defaultBoxShadow()
    {
        return 0;
    }

    public static function defaultFooter()
    {
        return 'rgba(0, 0, 0, 0.6)';
    }

    public static function defaultHeader()
    {
        return 'rgba(255,255,255,0.9)';
    }

    public static function defaultInput()
    {
        return 'rgba(255,255,255,0.9)';
    }

    public static function defaultHeaderLogoPadding()
    {
        return false;
    }

    public static function defaultHeaderTopRadius()
    {
        return true;
    }

    public static function defaultHeaderColor()
    {
        return 'rgba(255,255,255,0.9)';
    }

    public static function defaultPrimary()
    {
        return 'rgba(251, 224, 10, 0.9)';
    }

    public static function defaultTextColor()
    {
        return 'rgba(0,0,0,0.9)';
    }

    public static function defaultBackgroundImage()
    {
        return 'https://s3.eu-west-2.amazonaws.com/blackbx/locations/nearly.online/defaultBackground.jpg';
    }

    public static function defaultHeaderImage()
    {
        return 'https://s3.eu-west-2.amazonaws.com/blackbx/locations/nearly.online/defaultHeader.png';
    }

    public static function transparentImg()
    {
        return 'https://s3.eu-west-2.amazonaws.com/blackbx/locations/nearly.online/transparentImg.png';
    }

    public static function defaultRoundFormTopLeft()
    {
        return false;
    }

    public static function defaultRoundFormTopRight()
    {
        return false;
    }

    public static function defaultRoundFormBottomLeft()
    {
        return true;
    }

    public static function defaultRoundFormBottomRight()
    {
        return true;
    }

    public static function defaultRoundInputs()
    {
        return true;
    }

    public static function defaultHideFooter()
    {
        return false;
    }

    public static function defaultInterfaceColor()
    {
        return 'rgba(39, 58, 147, 1)';
    }

    public function getHeaderColor(): ?string
    {
        return $this->headerColor;
    }

    public function getInterfaceColor(): ?string
    {
        return $this->interfaceColor;
    }


    public function setBackgroundImage(string $backgroundImage)
    {
        $this->backgroundImage = $backgroundImage;
    }

    public function setHeaderImage(string $headerImage)
    {
        $this->headerImage = $headerImage;
    }

    public function setCustomCSS(string $customCss)
    {
        $this->customCss = $customCss;
    }

    public function getBackgroundImage(): ?string
    {
        return   $this->backgroundImage;
    }

    public function getHeaderImage(): ?string
    {
        return  $this->headerImage;
    }

    public function getCustomCSS(): ?string
    {
        return  $this->customCss;
    }

    public function getFullCss(): ?string
    {
        if (is_null($this->getCustomCSS())) {
            return null;
        }
        if (strlen($this->getCustomCSS()) <= 1 || empty($this->getCustomCSS())) {
            return null;
        }
        if (strpos($this->getCustomCSS(), 'https://') === false) {
            return null;
        }
        try {
            return file_get_contents($this->getCustomCSS());
        } catch (Exception $ex) {
            return null;
        }
    }

    public function partial()
    {
        return [
            'interface_color' => $this->interfaceColor,
            'background_image' => $this->backgroundImage,
            'header_image' => $this->headerImage,
        ];
    }


    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'background' => $this->background,
            'box_shadow' => $this->boxShadow,
            'footer' => $this->footer,
            'header_logo_padding' => $this->headerLogoPadding,
            'header_top_radius' => $this->headerTopRadius,
            'header_color' => $this->headerColor,
            'input' => $this->input,
            'background_image' => $this->backgroundImage,
            'header_image' => $this->headerImage,
            'primary' => $this->primary,
            'round_form_top_left' => $this->roundFormTopLeft,
            'round_form_top_right' => $this->roundFormTopRight,
            'round_form_bottom_left' => $this->roundFormBottomLeft,
            'round_form_bottom_right' => $this->roundFormBottomRight,
            'text_color' => $this->textColor,
            'round_inputs' => $this->roundInputs,
            'hide_footer' => $this->hideFooter,
            'custom_css' => $this->customCss,
            'message' => $this->message,
            'interface_color' => $this->interfaceColor,
            'get_full_css' => $this->getFullCss()
        ];
    }
}
