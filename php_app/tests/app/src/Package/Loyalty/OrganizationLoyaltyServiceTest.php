<?php

namespace StampedeTests\app\src\Package\Loyalty;

use App\Models\OauthUser;
use App\Models\Organization;
use App\Models\UserProfile;
use App\Package\Loyalty\OrganizationLoyaltyService;
use App\Package\Loyalty\Reward\RedeemableReward;
use App\Package\Loyalty\Stamps\StampContext;
use App\Package\Loyalty\StampScheme\SchemeUser;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;

class OrganizationLoyaltyServiceTest extends TestCase
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var Organization $organization
     */
    private $organization;

    /**
     * @var UserProfile $userProfile
     */
    private $userProfile;

    /**
     * @var OauthUser $stamper
     */
    private $stamper;

    protected function setUp(): void
    {
        $this->entityManager = DoctrineHelpers::createEntityManager();
        $this->entityManager->beginTransaction();
        $this->organization = $this
            ->entityManager
            ->getRepository(Organization::class)
            ->findOneBy(
                [
                    'name' => 'Some Company Ltd',
                ]
            );

        $this->userProfile = $this
            ->entityManager
            ->getRepository(
                UserProfile::class
            )
            ->findOneBy(
                [
                    'email' => 'alistair.judson@stampede.ai',
                ]
            );

        $this->stamper = $this
            ->entityManager
            ->getRepository(OauthUser::class)
            ->findOneBy(
                [
                    'email' => 'some.admin@stampede.ai',
                ]
            );
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
    }


    public function testGetOrganizationStampScheme()
    {
        $organizationLoyaltyService = new OrganizationLoyaltyService(
            $this->entityManager,
            $this->organization
        );

        // Create a scheme, with a reward of a coffee
        $scheme = $organizationLoyaltyService->createStampScheme(
            [
                'requiredStamps' => 9,
                'reward'         => [
                    'type' => 'item',
                    'name' => 'Coffee'
                ],
            ]
        );

        // Check the scheme requires 9 stamps to get a reward
        self::assertEquals(9, $scheme->getRequiredStamps());

        // Get the organization stamp scheme we just created
        $organizationStampScheme = $organizationLoyaltyService
            ->getOrganizationStampScheme($scheme->getId());

        // Since we just created it we should have no users on it
        self::assertCount(0, $organizationStampScheme->users());

        // Give a user 10 stamps on the scheme (more than one card)
        $organizationStampScheme
            ->schemeUser($this->userProfile)
            ->stamp(StampContext::organizationStamp($this->stamper), 10);


        $schemeUsers = $organizationStampScheme->users();
        // We should now have one user on the scheme
        self::assertCount(1, $schemeUsers);

        /** @var SchemeUser $firstSchemeUser */
        $firstSchemeUser = from($schemeUsers)
            ->first();


        // Since we gave the user 10 stamps on a 9 stamp scheme,
        // we should have a new card with just 1 stamp
        self::assertEquals(1, $firstSchemeUser->currentCard()->getCollectedStamps());

        // We should have one reward, as we've filled one card
        self::assertCount(1, $firstSchemeUser->redeemableRewards());

        // Give em 4 more stamps
        $firstSchemeUser->stamp(StampContext::organizationStamp($this->stamper), 4);

        // The current card should have 5 stamps now
        self::assertEquals(5, $firstSchemeUser->currentCard()->getCollectedStamps());

        /** @var RedeemableReward $firstReward */
        $firstReward = from($firstSchemeUser->redeemableRewards())
            ->first();

        // Lets redeem the reward we have now
        $reward  = $firstReward->redeem();
        $rewards = $firstSchemeUser->redeemableRewards();

        // We should have no rewards now
        self::assertCount(0, $rewards);

        // The reward should be called coffee
        self::assertEquals('Coffee', $reward->getName());

        // Fill the current card
        $firstSchemeUser->stamp(StampContext::organizationStamp($this->stamper), 4);

        // The current card should have 0 stamps now
        self::assertEquals(0, $firstSchemeUser->currentCard()->getCollectedStamps());

        // And the card should be inactive
        self::assertNull($firstSchemeUser->currentCard()->getActivatedAt());


        $secondRewards = $firstSchemeUser->redeemableRewards();
        // We should also have only one reward
        self::assertCount(1, $secondRewards);
        /** @var RedeemableReward $secondReward */
        $secondReward = from($secondRewards)
            ->first();

        // Redeem it
        $secondReward->redeem();

        // now we should have no redeemable rewards
        self::assertCount(0, $firstSchemeUser->redeemableRewards());
    }
}
