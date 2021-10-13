<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 08/02/2017
 * Time: 16:34
 */

namespace App\Controllers\Integrations\Stripe;

use App\Models\StripeSubscriptionPlans;
use App\Models\StripeSubscriptions;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;
use Stripe\SubscriptionItem;

class _StripeSubscriptionsItemsController
{
    protected $em;
    protected $stripeSubscriptionsController;
    private $logger;

    public function __construct(Logger $logger, EntityManager $em)
    {
        $this->em                            = $em;
        $this->logger = $logger;
        $this->stripeSubscriptionsController = new _StripeSubscriptionsController($logger, $em);
    }

    public function createSubscriptionItemRoute(Request $request, Response $response)
    {
        $body  = $request->getParsedBody();
        $subId = $request->getAttribute('subId');
        $send  = $this->createSubscriptionItem($request->getAttribute('accessUser'), $subId, $body);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getSubItemRoute(Request $request, Response $response)
    {
        $subItemId = $request->getAttribute('subItemId');
        $send      = $this->getSubItem($request->getAttribute('accessUser'), $request->getAttribute('subId'), $subItemId);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getSubItemsForSubRoute(Request $request, Response $response)
    {
        $subscription = $request->getAttribute('subId');
        $send         = $this->getSubItems($request->getAttribute('accessUser'), $subscription);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateSubItemRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();
        $send = $this->updateSubItem(
            $request->getAttribute('subId'),
            $request->getAttribute('subItemId'),
            $body,
            $request->getAttribute('accessUser')
        );

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateSubItemsRoute(Request $request, Response $response)
    {
        $send = $this->updateSubItems($request->getAttribute('accessUser'), $request->getAttribute('subId'), $request->getParsedBody()['plans']);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteSubItemRoute(Request $request, Response $response)
    {
        $subscription     = $request->getAttribute('subId');
        $subscriptionItem = $request->getAttribute('subItemId');

        $send = $this->deleteSubItem($subscription, $subscriptionItem, $request->getAttribute('accessUser'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function createSubscriptionItem(array $user, string $subId, array $body = [])
    {
        $belong = $this->subscriptionBelongsToUser($user, $subId);
        if ($belong['status'] !== 200) {
            return Http::status($belong['status'], $belong['message']);
        }

        $body['subId']            = $subId;
        $plansDontRequireBasePlan = ['CONTENT_FILTER', 'VPN'];
        if (!in_array($body['plan'], $plansDontRequireBasePlan)) {
            $check = $this->requiresBasePlan($subId);
            if ($check['status'] === 402) {
                return $check;
            }
        }

        $createItem = $this->createSubItem($body);
        if ($createItem['status'] === 200) {
            $this->insertSubscriptionItem($subId, $body['plan'], $createItem['message']['id']);
        }

        return Http::status($createItem['status'], $createItem['message']);
    }

    public function getSubItems(array $user, $subscription)
    {
        $belong = $this->subscriptionBelongsToUser($user, $subscription);
        if ($belong['status'] !== 200) {
            return Http::status($belong['status'], $belong['message']);
        }

        $qb = $this->em->createQueryBuilder()
            ->select('u')
            ->from(StripeSubscriptionPlans::class, 'u')
            ->where('u.subscriptionId = :id')
            ->setParameter('id', $subscription)
            ->getQuery()
            ->getArrayResult();
        if (empty($qb)) {
            return Http::status(404, 'SUBSCRIPTION_HAS_NO_ITEMS');
        }

        return Http::status(200, $qb);

    }

    public function getSubItem($user, $subscription, $subItemId)
    {
        $belong = $this->subscriptionBelongsToUser($user, $subscription);
        if ($belong['status'] !== 200) {
            return Http::status($belong['status'], $belong['message']);
        }
        $qb = $this->em->createQueryBuilder()
            ->select('u')
            ->from(StripeSubscriptionPlans::class, 'u')
            ->where('u.subscriptionItemId = :id')
            ->setParameter('id', $subItemId)
            ->getQuery()
            ->getArrayResult();
        if (empty($qb)) {
            return Http::status(404, 'COULD_NOT_FIND_ITEM');
        }

        return Http::status(200, $qb[0]);
    }

    public function deleteSubItem($subscriptionId, $subItem, $user)
    {
        $belong = $this->subscriptionBelongsToUser($user, $subscriptionId);
        if ($belong['status'] !== 200) {
            return Http::status($belong['status'], $belong['message']);
        }

        $getSubItem = $this->em->createQueryBuilder()
            ->select('u.subscriptionItemId')
            ->from(StripeSubscriptionPlans::class, 'u')
            ->where('u.subscriptionId = :subId')
            ->andWhere('u.subscriptionItemId = :item')
            ->setParameter('subId', $subscriptionId)
            ->setParameter('item', $subItem)
            ->getQuery()
            ->getArrayResult();
        if (empty($getSubItem)) {
            return Http::status(404, 'COULD_NOT_FIND_ITEM_ID_FOR_SUBSCRIPTION');
        }
        $delete = $this->deleteSubItemInStripe($getSubItem[0]['subscriptionItemId']);
        if ($delete['status'] === 200) {
            $deleteReference = $this->em->createQueryBuilder()
                ->update(StripeSubscriptionPlans::class, 'SSI')
                ->set('SSI.active', ':inactive')
                ->where('SSI.subscriptionItemId = :itemId')
                ->setParameter('inactive', 0)
                ->setParameter('itemId', $getSubItem[0]['subscriptionItemId'])
                ->getQuery()
                ->execute();
            if ($deleteReference === 1) {
                return Http::status(200, $delete['message']);
            }
        }

        return Http::status($delete['status'], $delete['message']);
    }

    public function updateSubItems($user, $subscriptionId, $subItems)
    {
        $userCheck = $this->subscriptionBelongsToUser($user, $subscriptionId);
        if ($userCheck['status'] !== 200) {
            return Http::status($userCheck['status'], $userCheck['message']);
        }

        $size     = sizeof($subItems);
        $count    = 0;
        $response = [];
        foreach ($subItems as $item) {
            if (isset($item['subItemId'])) {
                $update = $this->updateSubItemInStripe($item['subItemId'], ['plan' => $item['planId']]);
                if ($update['status'] === 200) {
                    $response[] = $update['message'];
                    $count      = $count + 1;
                }
            } else {
                $create = $this->createSubItem([
                    'subId' => $subscriptionId,
                    'plan'  => $item['planId']
                ]);
                if ($create['status'] === 200) {
                    $response[] = $create['message'];
                    $count      = $count + 1;
                }
            }
        }
        if ($count === $size) {
            return Http::status(200, $response);
        }

        return Http::status(206, $response);
    }

    public function insertSubscriptionItem($subId, $planName, $itemId)
    {
        $newSubItem                     = new StripeSubscriptionPlans;
        $newSubItem->subscriptionId     = $subId;
        $newSubItem->subscriptionItemId = $itemId;
        $newSubItem->planId             = $planName;
        $this->em->persist($newSubItem);
        $this->em->flush();
    }

    public function createSubItem($body = [])
    {
        $subItem = function ($values) {
            return SubscriptionItem::create([
                'subscription' => $values['subId'],
                'plan'         => $values['plan'],
                'prorate'      => true
            ]);
        };

        return $this->stripeSubscriptionsController->handleErrors($subItem, $body);
    }

    public function updateSubItemViaWebhook($subscriptionId, $subItemId, $plan)
    {
        $newSubscriptionPlan                     = new StripeSubscriptionPlans;
        $newSubscriptionPlan->subscriptionId     = $subscriptionId;
        $newSubscriptionPlan->subscriptionItemId = $subItemId;
        $newSubscriptionPlan->planId             = $plan;
        $newSubscriptionPlan->active             = 1;
        $this->em->persist($newSubscriptionPlan);
        $this->em->flush();
    }

    public function updateSubItem($subId, $subItem = '', $body = [], $user)
    {
        $check = $this->subscriptionBelongsToUser($user, $subId);
        if ($check['status'] === 200) {
            $hasBasePlan = $this->requiresBasePlan($subId);
            if ($hasBasePlan['status'] === 402) {
                return $hasBasePlan;
            }

            $updateStripe = $this->updateSubItemInStripe($subItem, $body);
            if ($updateStripe['status'] === 200) {
                if ($body['plan'] === 'MEDIUM') {
                    $update = $this->em->createQueryBuilder()
                        ->update(StripeSubscriptionPlans::class, 'u')
                        ->set('u.active', ':inactive')
                        ->where('u.subscriptionId = :subId')
                        ->andWhere('u.planId = :filter')
                        ->setParameter('inactive', 0)
                        ->setParameter('subId', $subId)
                        ->setParameter('filter', 'CONTENT_FILTER')
                        ->getQuery()
                        ->execute();
                    if ($update === 1) {
                        $subItem = $this->em->createQueryBuilder()
                            ->select('u.subscriptionItemId')
                            ->from(StripeSubscriptionPlans::class, 'u')
                            ->where('u.planId = :filter')
                            ->setParameter('filter', 'CONTENT_FILTER')
                            ->getQuery()
                            ->getArrayResult();
                        $this->deleteSubItemInStripe($subItem[0]);
                    }
                }
                if ($body['plan'] === 'LARGE') {
                    $update = $this->em->createQueryBuilder()
                        ->update(StripeSubscriptionPlans::class, 'u')
                        ->set('u.active', ':inactive')
                        ->where('u.subscriptionId = :subId')
                        ->andWhere('u.planId = :filter OR u.planId =:other')
                        ->setParameter('inactive', 0)
                        ->setParameter('subId', $subId)
                        ->setParameter('filter', 'CONTENT_FILTER')
                        ->setParameter('other', 'MARKETING_SM')
                        ->getQuery()
                        ->execute();
                    if ($update === 1) {
                        $subItems = $this->em->createQueryBuilder()
                            ->select('u.subscriptionItemId')
                            ->from(StripeSubscriptionPlans::class, 'u')
                            ->where('u.planId = :filter OR u.planId =:other')
                            ->setParameter('filter', 'CONTENT_FILTER')
                            ->setParameter('other', 'MARKETING_SM')
                            ->getQuery()
                            ->getArrayResult();
                        foreach ($subItems as $subItem) {
                            $this->deleteSubItemInStripe($subItem);
                        }
                    }
                }

                return Http::status(200, 'SUB_ITEM_UPDATED');
            }

            return Http::status($updateStripe['status'], $updateStripe['message']);
        }

        return Http::status($check['status'], $check['message']);
    }

    public function updateSubItemInStripe($subItem = '', $body = [])
    {
        $body['subItemID'] = $subItem;
        $update            = function ($values) {
            $sub = SubscriptionItem::retrieve($values['subItemID']);
            unset($values['subItemID']);
            $sub->plan    = $values['plan'];
            $sub->prorate = true;

            return $sub->save();
        };

        return $this->stripeSubscriptionsController->handleErrors($update, $body);
    }

    public function deleteSubItemInStripe($subId = '')
    {
        $delete = function ($sub) {
            $ret          = SubscriptionItem::retrieve($sub);
            $ret->prorate = true;

            return $ret->delete();
        };

        return $this->stripeSubscriptionsController->handleErrors($delete, $subId);
    }

    public function requiresBasePlan($subId)
    {
        $qb            = $this->em->createQueryBuilder();
        $basePlanCheck = $qb->select('u')
            ->from(StripeSubscriptionPlans::class, 'u')
            ->where('u.subscriptionId = :subId')
            ->andWhere('u.planId = :b OR u.planId = :s OR u.planId = :g')
            ->andWhere('u.active = :active')
            ->setParameter('subId', $subId)
            ->setParameter('b', 'SMALL')
            ->setParameter('s', 'MEDIUM')
            ->setParameter('g', 'LARGE')
            ->setParameter('active', 1)
            ->getQuery()
            ->getArrayResult();
        if (empty($basePlanCheck)) {
            return Http::status(402, 'REQUIRES_AN_ACTIVE_PAID_BASE_PLAN');
        }

        return Http::status(200, $basePlanCheck[0]);
    }

    public function subscriptionBelongsToUser($user, $sub)
    {
        $subBelongQb = $this->em->createQueryBuilder();
        $check       = $subBelongQb->select('u')
            ->from(StripeSubscriptions::class, 'u')
            ->where('u.subscriptionId = :sub')
            ->andWhere('u.createdBy = :user')
            ->setParameter('sub', $sub)
            ->setParameter('user', $user['uid'])
            ->getQuery()
            ->getArrayResult();
        if (!empty($check)) {
            return Http::status(200, 'SUBSCRIPTION_BELONGS_TO_USER');
        }

        return Http::status(403, 'SUBSCRIPTION_DOES_NOT_BELONG_TO_USER');
    }
}
