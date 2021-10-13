<?php

namespace App\Package\Segments\Database\BaseQueries;

use App\Models\BouncedEmails;
use App\Models\DataSources\OrganizationRegistration;
use App\Models\Organization;
use App\Models\UserProfile;
use App\Package\Segments\Database\Aliases\Exceptions\InvalidClassNameException;
use App\Package\Segments\Database\Joins\Exceptions\ClassNotInPoolException;
use App\Package\Segments\Database\OrderBy;
use App\Package\Segments\Database\Parse\Context;
use App\Package\Segments\Database\BaseQuery;
use App\Package\Segments\Fields\Exceptions\InvalidClassException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertiesException;
use App\Package\Segments\Fields\Exceptions\InvalidPropertyAliasException;
use App\Package\Segments\Fields\Exceptions\InvalidTypeException;
use App\Package\Segments\Fields\FieldList;
use App\Package\Segments\Fields\StandardField;
use App\Package\Segments\Values\Arguments\Argument;
use App\Package\Segments\Values\Arguments\ArgumentValue;
use App\Package\Segments\Values\Arguments\Exceptions\InvalidStringException;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use DoctrineExtensions\Query\Mysql\Field;

/**
 * Class OrganizationRegistrationBaseQuery
 * @package App\Package\Segments\Database\BaseQueries
 */
class OrganizationRegistrationBaseQuery implements BaseQuery
{
	private static $modePropertyMap = [
		Context::MODE_ALL   => 'dataOptInAt',
		Context::MODE_SMS   => 'smsOptInAt',
		Context::MODE_EMAIL => 'emailOptInAt',
	];

	/**
	 * @var Organization $organization
	 */
	private $organization;

	/**
	 * @var FieldList $fieldList
	 */
	private $fieldList;

	/**
	 * OrganizationRegistrationBaseQuery constructor.
	 * @param Organization $organization
	 * @param FieldList | null $fieldList
	 * @throws InvalidClassException
	 * @throws InvalidPropertiesException
	 * @throws InvalidPropertyAliasException
	 * @throws InvalidTypeException
	 */
	public function __construct(
		Organization $organization,
		?FieldList $fieldList = null
	) {
		if ($fieldList === null) {
			$fieldList = FieldList::default();
		}
		$this->organization = $organization;
		$this->fieldList    = $fieldList;
	}

	/**
	 * @return Field[]
	 * @throws InvalidClassException
	 * @throws InvalidPropertiesException
	 */
	public function groupByFields(): array
	{

		return [
			StandardField::integerId(
				OrganizationRegistration::class,
				'profileId'
			)
		];
	}


	/**
	 * @return string
	 */
	public function baseClassName(): string
	{
		return OrganizationRegistration::class;
	}

	/**
	 * @param QueryBuilder $builder
	 * @param Context $context
	 * @return QueryBuilder
	 * @throws ClassNotInPoolException
	 * @throws InvalidClassNameException
	 * @throws InvalidStringException
	 */
	public function queryBuilder(QueryBuilder $builder, Context $context): QueryBuilder
	{


		//var_dump($context->aliasPropertyName(BouncedEmails::class, 'email'));
		$mode  = $context->getMode();
		$query =  $builder
			->where(
				$this->whereClausePredicate($context)
			);

		if ($mode === Context::MODE_EMAIL) {
			$profile = $context->aliasPropertyName(UserProfile::class, 'email');
			$query = $query->leftJoin(
				BouncedEmails::class,
				'bounce',
				'WITH',
				"${profile} = bounce.email"
			);
		}

		return $query;
	}

	/**
	 * @return Field[]
	 */
	public function baseFields(): array
	{
		return $this
			->fieldList
			->filterByKey(
				'id',
				'email',
				'first',
				'last',
				'createdAt',
				'lastInteractedAt'
			);
	}

	/**
	 * @return OrderBy[]
	 */
	public function ordering(): array
	{
		return [
			OrderBy::desc($this->fieldList->getField('lastInteractedAt'))
		];
	}

	/**
	 * @param Context $context
	 * @return string[]
	 * @throws ClassNotInPoolException
	 * @throws InvalidClassNameException
	 */
	public function reachSelect(Context $context): array
	{
		$countProperty = $context->aliasPropertyName(
			OrganizationRegistration::class,
			'profileId'
		);
		return from(self::$modePropertyMap)
			->select(
				function (string $property, string $mode) use ($context, $countProperty): string {
					$aliasedProperty = $context->aliasPropertyName(
						OrganizationRegistration::class,
						$property
					);
					return "COUNT(DISTINCT IF(${aliasedProperty} IS NULL, NULL, ${countProperty})) AS ${mode}";
				}
			)
			->toValues()
			->toArray();
	}

	/**
	 * @param Context $context
	 * @return Andx
	 * @throws ClassNotInPoolException
	 * @throws InvalidClassNameException
	 * @throws InvalidStringException
	 */
	private function whereClausePredicate(Context $context): Andx
	{
		$expr  = new Expr();
		$mode  = $context->getMode();
		$parts = [
			$expr->eq(
				$this->aliasPropertyName($context, 'organizationId'),
				$context->parameter($this->organizationIdArgument())
			),
			$expr->isNotNull(
				$this->aliasPropertyName($context, 'dataOptInAt')
			)
		];
		if ($mode !== Context::MODE_ALL) {
			$parts[] = $expr->isNotNull(
				$this->aliasPropertyName($context, self::$modePropertyMap[$mode])
			);
		}
		if ($mode === Context::MODE_EMAIL) {
			$parts[] = $expr->isNull('bounce.email');
		}
		return $expr->andX(...$parts);
	}

	/**
	 * @return Argument
	 * @throws InvalidStringException
	 */
	private function organizationIdArgument(): Argument
	{
		return ArgumentValue::stringValue(
			'organizationId',
			$this->organization->getId()->toString()
		);
	}

	/**
	 * @param Context $context
	 * @param string $propertyName
	 * @return string
	 * @throws InvalidClassNameException
	 * @throws ClassNotInPoolException
	 */
	private function aliasPropertyName(Context $context, string $propertyName): string
	{
		return $context->aliasPropertyName(
			$this->baseClassName(),
			$propertyName
		);
	}
}
