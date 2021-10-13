<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 07/09/2017
 * Time: 13:01
 */

namespace App\Controllers\Notifications;

use App\Models\Notifications\Notification;
use App\Models\Notifications\UserNotificationLists;
use App\Models\Notifications\UserNotifications;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Slim\Http\Response;
use Slim\Http\Request;

class _ReleaseNotifyController
{
    protected $em;
    private $connectCache;

    public function __construct(EntityManager $em)
    {
        $this->em           = $em;
        $this->connectCache = new CacheEngine(getenv('CONNECT_REDIS'));
    }

    public function hasSeenReleaseRoute(Request $request, Response $response)
    {
        $send = $this->hasSeenRelease($request->getAttribute('user'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function loadNotificationsRoute(Request $request, Response $response)
    {
        $queryParams = $request->getQueryParams();

        $offset = 0;

        if (isset($queryParams['offset'])) {
            $offset = $queryParams['offset'];
        }

        $send = $this->loadNotifications($request->getAttribute('user'), $offset);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function notificationStatusRoute(Request $request, Response $response)
    {
        $send = $this->notificationStatus($request->getAttribute('user'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    /**
     * @param array $user
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */

    public function notificationStatus(array $user)
    {

        $return = $this->connectCache->fetch($user['uid'] . ':connectNotificationLists');
        if (is_bool($return)) {
            $hasList = $this->em->getRepository(UserNotificationLists::class)->findOneBy([
                'uid' => $user['uid']
            ]);

            $return = [
                'new' => false
            ];

            if (is_object($hasList)) {
                $return['new']       = $hasList->hasSeen;
                $return['listId']    = $hasList->notificationList;
                $return['updatedAt'] = $hasList->updatedAt;
            } else {
                $list = new UserNotificationLists($user['uid']);
                $this->em->persist($list);
                $this->em->flush();
                $return['new']       = $list->hasSeen;
                $return['listId']    = $list->notificationList;
                $return['updatedAt'] = $list->updatedAt;
            }

            $this->connectCache->save($user['uid'] . ':connectNotificationLists', $return);
        }

        if ($return['new'] === false) {

            $getNumberOfNotifications = $this->em->createQueryBuilder()
                ->select('COUNT(u.id) as num')
                ->from(UserNotifications::class, 'u')
                ->join(Notification::class, 'n', 'WITH', 'u.notificationId = n.id')
                ->where('u.userNotificationListId = :id')
                ->andWhere('n.createdAt BETWEEN :lastSeenTime AND :now')
                ->setParameter('id', $return['listId'])
                ->setParameter('lastSeenTime', $return['updatedAt'])
                ->setParameter('now', new \DateTime())
                ->getQuery()
                ->getArrayResult();

            $return['notificationCount'] = (int)$getNumberOfNotifications[0]['num'];
        }

        return Http::status(200, $return);
    }

    /**
     * @param array $user
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */

    public function hasSeenRelease(array $user)
    {
        $find = $this->em->getRepository(UserNotificationLists::class)->findOneBy([
            'uid' => $user['uid']
        ]);

        if (is_object($find)) {
            $find->hasSeen   = true;
            $find->updatedAt = new \DateTime();
        } else {
            $find          = new UserNotificationLists($user['uid']);
            $find->hasSeen = true;
            $this->em->persist($find);
        }
        $this->em->flush();

        return Http::status(200, $find->hasSeen);
    }

    /**
     * @param array $user
     * @param $offset
     * @return array
     */

    public function loadNotifications(array $user, $offset)
    {

        $getListId = $this->em->createQueryBuilder()
            ->select('u . notificationList')
            ->from(UserNotificationLists::class, 'u')
            ->where('u . uid = :s')
            ->setParameter('s', $user['uid'])
            ->getQuery()
            ->getArrayResult();

        $getAll  = $this->em->createQueryBuilder()
            ->select('n . title, n . objectId, n . kind, n . status, n . link, n . createdAt')
            ->from(UserNotifications::class, 'u')
            ->join(Notification::class, 'n', 'WITH', 'u . notificationId = n . id')
            ->where('u . userNotificationListId = :id')
            ->setParameter('id', $getListId[0]['notificationList'])
            ->orderBy('n . createdAt', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults(5);
        $results = new Paginator($getAll);
        $results->setUseOutputWalkers(false);

        $getAll = $results->getIterator()->getArrayCopy();

        if (empty($getAll)) {
            return Http::status(204, []);
        }

        $return = [
            'notifications' => $getAll,
            'has_more'      => false,
            'total'         => count($results),
            'next_offset'   => $offset + 5
        ];

        if ($offset <= $return['total'] && count($getAll) !== $return['total']) {
            $return['has_more'] = true;
        }

        return Http::status(200, $return);
    }
}
