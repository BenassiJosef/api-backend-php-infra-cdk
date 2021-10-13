<?php


namespace App\Package\Loyalty\App;

use App\Models\Loyalty\LoyaltyStampScheme;
use JsonSerializable;

/**
 * Class LoyaltyBranding
 * @package App\Package\Loyalty\App
 */
class LoyaltyBranding implements JsonSerializable
{
	/**
	 * @param LoyaltyStampScheme $scheme
	 * @return LoyaltyBranding
	 */
	public static function fromScheme(LoyaltyStampScheme $scheme): self
	{
		return new self(
			$scheme->getBackgroundColour(),
			$scheme->getForegroundColour(),
			$scheme->getLabelColour(),
			$scheme->getIcon(),
			$scheme->getBackgroundImage(),
			$scheme->getLabelIcon()
		);
	}

	/**
	 * @param array $data
	 * @return LoyaltyBranding
	 */
	public static function fromArray(array $data): self
	{
		return new self(
			$data['backgroundColour'],
			$data['foregroundColour'],
			$data['labelColour'],
			$data['icon'],
			$data['backgroundImage'],
			$data['labelIcon']
		);
	}

	/**
	 * @var string | null $backgroundColour
	 */
	private $backgroundColour;

	/**
	 * @var string | null $foregroundColour
	 */
	private $foregroundColour;

	/**
	 * @var string | null $labelColour
	 */
	private $labelColour;

	/**
	 * @var string | null $icon
	 */
	private $icon;

	/**
	 * @var string | null $backgroundImage
	 */
	private $backgroundImage;

	/**
	 * @var string | null $labelIcon
	 */
	private $labelIcon;

	/**
	 * LoyaltyBranding constructor.
	 * @param string|null $backgroundColour
	 * @param string|null $foregroundColour
	 * @param string|null $labelColour
	 * @param string|null $icon
	 * @param string|null $backgroundImage
	 */
	public function __construct(
		?string $backgroundColour = null,
		?string $foregroundColour = null,
		?string $labelColour = null,
		?string $icon = null,
		?string $backgroundImage = null,
		?string $labelIcon = null
	) {
		$this->backgroundColour = $backgroundColour;
		$this->foregroundColour = $foregroundColour;
		$this->labelColour      = $labelColour;
		$this->icon             = $icon;
		$this->backgroundImage  = $backgroundImage;
		$this->labelIcon  = $labelIcon;
	}


	/**
	 * @return string|null
	 */
	public function getBackgroundColour(): ?string
	{
		return $this->backgroundColour;
	}

	/**
	 * @return string|null
	 */
	public function getForegroundColour(): ?string
	{
		return $this->foregroundColour;
	}

	/**
	 * @return string|null
	 */
	public function getLabelColour(): ?string
	{
		return $this->labelColour;
	}

	/**
	 * @return string|null
	 */
	public function getIcon(): ?string
	{
		return $this->icon;
	}

	/**
	 * @return string|null
	 */
	public function getBackgroundImage(): ?string
	{
		return $this->backgroundImage;
	}

	/**
	 * @return string|null
	 */
	public function getLabelIcon(): ?string
	{
		return $this->labelIcon;
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4
	 */
	public function jsonSerialize()
	{
		return [
			'backgroundColour' => $this->backgroundColour,
			'foregroundColour' => $this->foregroundColour,
			'labelColour'      => $this->labelColour,
			'icon'             => $this->icon,
			'backgroundImage'  => $this->backgroundImage,
			'labelIcon' => $this->labelIcon
		];
	}
}
