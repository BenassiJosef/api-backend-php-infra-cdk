<?php


namespace App\Package\Organisations;

/**
 * Class OrganizationSettingsDefinition
 * @package App\Package\Organisations
 */
class OrganizationSettingsDefinition implements \JsonSerializable
{
	/**
	 * @param array $data
	 * @return static
	 */
	public static function fromArray(array $data): self
	{
		$def                = new self();
		$def->checkoutEmail = $data['canSendCheckoutEmail'] ?? $def->canSendCheckoutEmail();
		return $def;
	}

	/**
	 * @var bool $checkoutEmail
	 */
	private $checkoutEmail;

	/**
	 * OrganizationSettings constructor.
	 */
	public function __construct()
	{
		$this->checkoutEmail = false;
	}

	/**
	 * @return bool
	 */
	public function canSendCheckoutEmail(): bool
	{
		return $this->checkoutEmail;
	}

	/**
	 * @return $this
	 */
	public function enableCheckoutEmails(): self
	{
		$this->checkoutEmail = true;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function disableCheckoutEmails(): self
	{
		$this->checkoutEmail = false;
		return $this;
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
			'canSendCheckoutEmail' => $this->canSendCheckoutEmail(),
		];
	}
}
