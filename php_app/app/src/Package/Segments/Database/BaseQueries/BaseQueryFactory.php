<?php

namespace App\Package\Segments\Database\BaseQueries;

use App\Models\Organization;
use App\Package\Segments\Database\BaseQueries\Exceptions\UnknownBaseQueryException;
use App\Package\Segments\Database\BaseQuery;

class BaseQueryFactory
{
	const ORGANIZATION_REGISTRATION = 'organization-registration';

	const ORGANIZATION_REVIEW = 'organization-review';

	const ORGANIZATION_LOYALTY = 'organization-loyalty';

	const ORGANIZATION_GIFTING = 'organization-gifting';

	/**
	 * @param string $type
	 * @param Organization $organization
	 * @return BaseQuery
	 * @throws UnknownBaseQueryException
	 */
	public function make(string $type, Organization $organization): BaseQuery
	{
		if ($type !== self::ORGANIZATION_REGISTRATION) {
			throw new UnknownBaseQueryException($type, [self::ORGANIZATION_REGISTRATION]);
		}
		return new OrganizationRegistrationBaseQuery($organization);
	}
}
