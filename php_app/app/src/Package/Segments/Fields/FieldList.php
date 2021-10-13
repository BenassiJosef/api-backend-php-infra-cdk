<?php

namespace App\Package\Segments\Fields;

use App\Models\BouncedEmails;
use App\Models\DataSources\DataSource;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\DataSources\RegistrationSource;
use App\Models\GiftCard;
use App\Models\Loyalty\LoyaltyStampCard;
use App\Models\Reviews\UserReview;
use App\Models\UserProfile;
use App\Models\UserRegistration;
use App\Package\Segments\Fields\Exceptions\FieldNotFoundException;
use App\Package\Segments\Fields\Exceptions\InvalidClassException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertiesException;
use App\Package\Segments\Fields\Exceptions\InvalidTypeException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertyAliasException;
use JsonSerializable;

/**
 * Class FieldList
 * @package App\Package\Segments\Fields
 */
class FieldList implements JsonSerializable
{
	/**
	 * @return static
	 * @throws InvalidClassException
	 * @throws InvalidPropertiesException
	 * @throws InvalidPropertyAliasException
	 * @throws InvalidTypeException
	 */
	public static function default(): self
	{
		return new self(
			StandardField::integerId(
				UserProfile::class
			),
			StandardField::integer(
				'rating',
				UserReview::class,
				'rating',
				'MAX'
			),
			StandardField::string(
				'sentiment',
				UserReview::class
			),
			StandardField::datetime(
				'reviewCreatedAt',
				UserReview::class,
				'createdAt'
			),
			StandardField::integer(
				'stamps',
				LoyaltyStampCard::class,
				'collectedStamps'
			),
			StandardField::datetime(
				'loyaltyCreatedAt',
				LoyaltyStampCard::class,
				'createdAt',
			),
			StandardField::datetime(
				'loyaltyLastStampedAt',
				LoyaltyStampCard::class,
				'lastStampedAt',
			),

			StandardField::integer(
				'giftAmount',
				GiftCard::class,
				'amount'
			),
			StandardField::integer(
				'numberOfInteractions',
				RegistrationSource::class,
				'interactions',
				'MAX'
			),
			StandardField::datetime(
				'giftActivatedAt',
				GiftCard::class,
				'activatedAt'
			),
			StandardField::datetime(
				'giftRedeemedAt',
				GiftCard::class,
				'redeemedAt'
			),
			StandardField::datetime(
				'lastInteractedAt',
				OrganizationRegistration::class
			),
			StandardField::datetime(
				'createdAt',
				OrganizationRegistration::class
			),
			StandardField::string(
				'serial',
				RegistrationSource::class
			),
			StandardField::string(
				'dataSource',
				DataSource::class,
				'key'
			),
			StandardField::string(
				'email',
				UserProfile::class
			),
			StandardField::string(
				'first',
				UserProfile::class
			),
			StandardField::string(
				'last',
				UserProfile::class
			),
			StandardField::string(
				'phone',
				UserProfile::class
			),
			StandardField::string(
				'postcode',
				UserProfile::class
			),
			AliasedMultiField::yeardate(
				'birthday',
				UserProfile::class,
				[
					'day'   => 'birthDay',
					'month' => 'birthMonth'
				],
			),
			StandardField::string(
				'gender',
				UserProfile::class
			),
			StandardField::string(
				'country',
				UserProfile::class
			),
			StandardField::boolean(
				'isVisit',
				DataSource::class
			),
		);
	}

	/**
	 * @var Field[] $fields
	 */
	private $fields;

	/**
	 * FieldList constructor.
	 * @param Field[] $fields
	 */
	public function __construct(Field ...$fields)
	{
		$this->fields = from($fields)
			->select(
				function (Field $field): Field {
					return $field;
				},
				function (Field $field): string {
					return strtolower($field->getKey());
				}
			)
			->toArray();
	}

	/**
	 * @param string $key
	 * @return Field
	 * @throws FieldNotFoundException
	 */
	public function getField(string $key): Field
	{
		$key = strtolower($key);
		if (!$this->hasField($key)) {
			throw new FieldNotFoundException($key);
		}
		return $this->fields[$key];
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function hasField(string $key): bool
	{
		return array_key_exists($key, $this->fields);
	}

	/**
	 * @return Field[]
	 */
	public function fields(): array
	{
		return $this->fields;
	}

	/**
	 * @param string ...$keys
	 * @return Field[]
	 */
	public function filterByKey(string ...$keys): array
	{
		if (count($keys) === 0) {
			return [];
		}
		// create a set of key names, for O(1) lookups
		$keyedMap = from($keys)
			->select(
				function (string $key): bool {
					return true;
				},
				function (string $key): string {
					return $key;
				}
			)
			->toArray();
		return from($this->fields)
			->where(
				function (Field $field) use ($keyedMap) {
					return array_key_exists($field->getKey(), $keyedMap);
				}
			)
			->toArray();
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
		return from($this->fields)
			->select(
				function (Field $field): array {
					return [
						'key'  => $field->getKey(),
						'type' => $field->getType()
					];
				}
			)
			->toArray();
	}
}
