<?php

namespace StampedeTests\app\src\Package\Loyalty\StampScheme;

use App\Models\Loyalty\LoyaltyStampCard;
use App\Models\Loyalty\LoyaltyStampScheme;
use App\Models\Organization;
use App\Models\UserProfile;
use App\Package\Loyalty\Stamps\StampContext;
use App\Package\Loyalty\StampScheme\LazySchemeUser;
use App\Package\Loyalty\StampScheme\StampSchemeFactory;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use StampedeTests\Helpers\DoctrineHelpers;
use voku\CssToInlineStyles\Exception;

class SchemeUserTest extends TestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var UserProfile | null
     */
    private $userProfile;

    /**
     * @var Organization|null
     */
    private $organization;
    /**
     * @var LoyaltyStampScheme
     */
    private $scheme;

    protected function setUp(): void
    {
        $this->entityManager = DoctrineHelpers::createEntityManager();
        $this->entityManager->beginTransaction();

        $this->userProfile = $this
            ->entityManager
            ->getRepository(UserProfile::class)
            ->findOneBy(
                [
                    'email' => 'alistair.judson@stampede.ai'
                ]
            );

        $this->organization = $this
            ->entityManager
            ->getRepository(Organization::class)
            ->findOneBy(
                [
                    'name' => 'Some Company Ltd'
                ]
            );

        $this->scheme = StampSchemeFactory::defaultStampSchemeFactory()
                                          ->make(
                                              $this->organization,
                                              [
                                                  'isActive'       => true,
                                                  'requiredStamps' => 9,
                                                  'reward'         => [
                                                      'type' => 'item',
                                                      'name' => 'A coffee',
                                                      'code' => 'COVFEFE',
                                                  ],
                                              ]
                                          );
        $this->entityManager->persist($this->scheme);
        $this->entityManager->flush();
    }

    public function testCurrentCard()
    {
        $schemeUser = new LazySchemeUser(
            $this->entityManager,
            $this->scheme,
            $this->userProfile
        );

        $userCards = $this
            ->entityManager
            ->getRepository(LoyaltyStampCard::class)
            ->findBy(
                [
                    'profileId' => $this->userProfile->getId(),
                    'schemeId'  => $this->scheme->getId(),
                ]
            );

        // Ensure the user has no cards
        self::assertCount(0, $userCards);

        // Ask for the user's active card, it should be lazily created
        $firstActiveCardStamps = $schemeUser->currentCard()->getRemainingStamps();

        // Ask for it again
        $secondActiveCardId = $schemeUser->currentCard()->getRemainingStamps();

        // Assert that the second time we ask for a card we get the same card
        self::assertEquals($firstActiveCardStamps, $secondActiveCardId);
    }

    public function testStamp()
    {
        $schemeUser = new LazySchemeUser(
            $this->entityManager,
            $this->scheme,
            $this->userProfile
        );

        $userCards = $this
            ->entityManager
            ->getRepository(LoyaltyStampCard::class)
            ->findBy(
                [
                    'profileId' => $this->userProfile->getId(),
                    'schemeId'  => $this->scheme->getId(),
                ]
            );

        // Ensure the user has no cards
        self::assertCount(0, $userCards);

        // The cards have capacity for 9 stamps, so by doing 19 stamps
        // we should have two cards with 9 stamps, and one with one
        $schemeUser->stamp(StampContext::emptyContext(), 19);

        $userCardsAfterStamping = $this
            ->entityManager
            ->getRepository(LoyaltyStampCard::class)
            ->findBy(
                [
                    'profileId' => $this->userProfile->getId(),
                    'schemeId'  => $this->scheme->getId(),
                ]
            );

        // Ensure the user has no cards
        self::assertCount(3, $userCardsAfterStamping);

        self::assertCount(2, $schemeUser->redeemableRewards());

        self::assertEquals(1, $schemeUser->currentCard()->getCollectedStamps());
    }


    protected function tearDown(): void
    {
        $this->entityManager->commit();
    }
}
