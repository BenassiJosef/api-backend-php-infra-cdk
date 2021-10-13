<?php


namespace App\Controllers\Billing\Subscriptions;

use App\Controllers\Integrations\Mikrotik\MikrotikCreationController;
use App\Controllers\Locations\Creation\LocationCreationFactory;
use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use App\Models\Organization;

/**
 * Class DummySubscriptionController
 * @package App\Controllers\Billing\Subscriptions
 */
class DummySubscriptionCreator implements SubscriptionCreator
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * DummySubscriptionController constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Organization $customerOrganisation
     * @param array $body
     * @return array
     * @throws \Exception
     */
    public function createSubscription(Organization $customerOrganisation, array $body)
    {
        $creationRequest = SubscriptionCreationRequest::createFromArray($body);
        if (is_array($creationRequest)) {
            return $creationRequest;
        }

        $locationCreationFactory = new LocationCreationFactory(
            $this->entityManager,
            $creationRequest->getMethodName(),
            $creationRequest->getMethodSerial()
        );

        $method = $locationCreationFactory->getInstance();

        if (is_null($method->getSerial())) {
            if ($method instanceof MikrotikCreationController) {
                return Http::status(409, 'MIKROTIK_SERIAL_IS_EMPTY');
            }
            $method->setSerial($method::serialGenerator());
        } elseif (!$method->locationCreationChecksController->executePreCreationChecks()) {
            return Http::status(409, $method->locationCreationChecksController->getReasonForFailure());
        }

        $now          = new \DateTime('now');
        $aWeek        = new \DateInterval('P7D');
        $aWeekFromNow = $now->add($aWeek);

        return [
            'status'  => 200,
            'message' => [
                'url'        => 'https://example.com/dummy-chargebee-url',
                'expires_at' => $aWeekFromNow->getTimestamp(),
                'cf_serial'  => $method->getSerial(),
                'cf_method'  => $method->getVendorForChargeBee()
            ],
        ];
    }

}