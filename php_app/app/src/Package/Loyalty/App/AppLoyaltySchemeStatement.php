<?php


namespace App\Package\Loyalty\App;


use App\Models\UserProfile;
use Ramsey\Uuid\UuidInterface;

class AppLoyaltySchemeStatement implements \App\Package\Database\Statement
{

	/**
	 * @var UserProfile $userProfile
	 */
	private $userProfile;

	/**
	 * @var int $offset
	 */
	private $offset;

	/**
	 * @var int $limit
	 */
	private $limit;


	/**
	 * AppLoyaltySchemeStatement constructor.
	 * @param UserProfile $userProfile
	 * @param int $offset
	 * @param int $limit
	 */
	public function __construct(
		UserProfile $userProfile,
		int $offset = 0,
		int $limit = 25
	) {
		$this->userProfile = $userProfile;
		$this->offset      = $offset;
		$this->limit       = $limit;
	}

	/**
	 * @inheritDoc
	 */
	public function query(): string
	{
		return "SELECT 
    iq.organizationId,
	iq.organizationName,
    iq.schemeId,
    iq.backgroundColour,
    iq.foregroundColour,
    iq.labelColour,
 	iq.labelIcon,
    iq.icon,
    iq.backgroundImage,
    iq.requiredStamps,
    iq.reward,
    iq.terms,   
    JSON_ARRAYAGG(
		JSON_OBJECT(
			'serial',
            ls.serial,
            'name',
            ls.alias
        )
    ) AS locations,
    MAX(iq.cards) AS `cards`
FROM
    (SELECT 
        o.id AS organizationId,
        o.name AS organizationName,
            lss.id AS schemeId,
            lss.serial AS schemeSerial,
            lss.background_colour AS backgroundColour,
            lss.foreground_colour AS foregroundColour,
            lss.label_colour AS labelColour,
            lss.icon AS icon,
            lss.background_image AS backgroundImage,
 			lss.label_icon AS labelIcon,
            lss.required_stamps AS requiredStamps,
            lss.terms,
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
				JSON_OBJECT(
					'id', 
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
                    lsc.redeemed_at
				)
			) AS cards
    FROM
        `organization` o
    LEFT JOIN loyalty_stamp_scheme lss ON lss.organization_id = o.id
    LEFT JOIN loyalty_stamp_card lsc ON lss.id = lsc.scheme_id
    LEFT JOIN loyalty_reward lr ON lr.id = lss.reward_id
    WHERE lsc.id IS NOT NULL 
      AND lsc.profile_id = :userId
      AND lss.deleted_at IS NULL
      AND lsc.deleted_at IS NULL
    GROUP BY o.id, o.name , lss.id, lss.serial, lsc.profile_id
    ORDER BY MAX(COALESCE(lsc.last_stamped_at, lsc.created_at)) DESC
    LIMIT :limit OFFSET :offset) iq
LEFT JOIN location_settings ls ON ls.organization_id = iq.organizationId AND ls.alias IS NOT NULL
GROUP BY iq.schemeId, iq.organizationName, iq.organizationId";
	}

	/**
	 * @inheritDoc
	 */
	public function parameters(): array
	{
		return [
			'userId' => $this->userProfile->getId(),
			'offset' => $this->offset,
			'limit'  => $this->limit,
		];
	}
}
