<?php


namespace App\Package\Loyalty\StampScheme;


use App\Package\Database\Statement;
use Ramsey\Uuid\UuidInterface;

/**
 * Class SchemeUsersStatement
 * @package App\Package\Loyalty\StampScheme
 */
class SchemeUsersStatement implements Statement
{
    /**
     * @var UuidInterface $schemeId
     */
    private $schemeId;

    /**
     * @var int $limit
     */
    private $limit;

    /**
     * @var int $offset
     */
    private $offset;

    /**
     * SchemeUsersStatement constructor.
     * @param UuidInterface $schemeId
     * @param int $limit
     * @param int $offset
     */
    public function __construct(
        UuidInterface $schemeId,
        int $offset = 0,
        int $limit = 25
    ) {
        $this->schemeId = $schemeId;
        $this->limit    = $limit;
        $this->offset   = $offset;
    }


    /**
     * @inheritDoc
     */
    public function query(): string
    {
        return "SELECT 
    up.id AS id,
    up.first,
    up.last,
    up.email,
    up.phone,
    up.gender,
    up.postcode,
    up.birth_day AS birthDay,
    up.birth_month AS birthMonth,
    lss.id AS schemeId,
    lss.required_stamps AS requiredStamps,                    
    JSON_OBJECT(
	        	'id',
				lr.id,
                'organizationId',
				lr.organization_id,
				'name',
				lr.name,
                'code',
                lr.code,
                'amount',
                lr.amount,
                'currency',
                lr.currency,
                'type',
                lr.type,
                'createdAt',
                lr.created_at
	) AS reward,   
    JSON_ARRAYAGG(
        JSON_OBJECT('id',
                    lsc.id,
                    'schemeId',
                    lsc.scheme_id,
                    'profileId',
                    lsc.profile_id,
                    'collectedStamps',
                    lsc.collected_stamps,
                    'requiredStamps',
                    lss.required_stamps,
                    'createdAt',
                    lsc.created_at,
                    'lastStampedAt',
                    lsc.last_stamped_at,
                    'stampCooldownDuration',
                    lss.stamp_cooldown_duration,
                    'redeemedAt',
                    lsc.redeemed_at)
        ) AS cards,
	MAX(lsc.created_at) AS created_at
FROM
    user_profile up
        LEFT JOIN
    loyalty_stamp_card lsc ON lsc.profile_id = up.id
        LEFT JOIN 
    loyalty_stamp_scheme lss ON lss.id = lsc.scheme_id
		LEFT JOIN 
	loyalty_reward lr ON lss.reward_id = lr.id
WHERE
    lsc.id IS NOT NULL
    AND lsc.scheme_id = :schemeId
    AND lsc.redeemed_at IS NULL
    AND lsc.deleted_at IS NULL
    AND lss.deleted_at IS NULL
GROUP BY 
	up.id, 
	lss.id, 
	lss.required_stamps,
	up.first,
    up.last,
    up.email,
    up.phone,
    up.gender,
    up.postcode,
    up.birth_day,
    up.birth_month
ORDER BY MAX(lsc.created_at) DESC
LIMIT :limit OFFSET :offset ;";
    }

    /**
     * @inheritDoc
     */
    public function parameters(): array
    {
        return [
            'schemeId' => $this->schemeId->toString(),
            'limit'    => $this->limit,
            'offset'   => $this->offset,
        ];
    }
}