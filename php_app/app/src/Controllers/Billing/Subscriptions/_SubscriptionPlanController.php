<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 15/02/2017
 * Time: 14:45
 */

namespace App\Controllers\Billing\Subscriptions;

use App\Controllers\Members\CustomerPricingController;
use App\Models\Integrations\ChargeBee\Subscriptions;
use App\Models\Integrations\ChargeBee\SubscriptionsAddon;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _SubscriptionPlanController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function findBestSuitedPlansRoute(Request $request, Response $response)
    {
        $orgId       = $request->getAttribute('orgId');
        $currentUser = $request->getAttribute('user');
        $send        = $this->findBestSuitedPlans($currentUser, $orgId);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    /**
     *
     * @param array $user
     * @param array $plans
     * @return array
     */

    public function findBestSuitedPlans(array $user, string $orgId)
    {
        $output = [
            'addOns' => [],
            'plans'  => []
        ];

        foreach (Subscriptions::$currentPlanListChargeBee as $key => $plan) {
            if (strpos($plan, '_an') !== false) {
                continue;
            }

            if ($plan === 'starter') {
                $output['plans']['starter'] = [
                    'name'        => 'Starter',
                    'chargeBeeId' => 'starter'
                ];
            } elseif ($plan === 'all-in') {
                $output['plans']['allIn'] = [
                    'name'        => 'All In',
                    'chargeBeeId' => 'all-in'
                ];
            }
        }

        foreach (Subscriptions::$currentAddOns as $key => $addOn) {
            if (strpos($addOn, '_an') !== false) {
                continue;
            }

            if ($addOn === 'content-filter') {
                $output['addOns']['contentFilter'] = [
                    'name'        => 'Web Filtering',
                    'chargeBeeId' => 'content-filter'
                ];
            } elseif ($addOn === 'reviews') {
                $output['addOns']['reviews'] = [
                    'name'        => 'Reviews',
                    'chargeBeeId' => 'reviews'
                ];
            } elseif ($addOn === 'marketing-automation') {
                $output['addOns']['marketingAutomation'] = [
                    'name'        => 'Marketing Automation',
                    'chargeBeeId' => 'marketing-automation'
                ];
            } elseif ($addOn === 'custom-integration') {
                $output['addOns']['customIntegration'] = [
                    'name'        => 'Custom Integration',
                    'chargeBeeId' => 'custom-integration'
                ];
            } elseif ($addOn === 'stories') {
                $output['addOns']['stories'] = [
                    'name'        => 'Stories',
                    'chargeBeeId' => 'stories'
                ];
            }
        }

        if ($user['role'] === 0) {
            $output['plans']['demo'] = [
                'name'        => 'Demo',
                'chargeBeeId' => 'demo',
                'pricing'     => 0
            ];
        }

        $customerPricingController = new CustomerPricingController($this->em);
        $pricing                   = $customerPricingController->getPricing($orgId)['message'];

        foreach ($pricing as $key => $price) {
            if (!isset($output['plans'][$key]) && !isset($output['addOns'][$key])) {
                continue;
            }

            if (isset($output['plans'][$key])) {
                $output['plans'][$key]['pricing'] = $price;
                continue;
            }

            if (isset($output['addOns'][$key])) {
                $output['addOns'][$key]['pricing'] = $price;
                continue;
            }
        }

        $returnStructure = [
            'plans'  => [],
            'addOns' => []
        ];

        foreach ($output['plans'] as $planTitle => $plan) {
            $returnStructure['plans'][] = $plan;
        }

        foreach ($output['addOns'] as $planTitle => $plan) {
            $returnStructure['addOns'][] = $plan;
        }

        return Http::status(200, $returnStructure);
    }
}
