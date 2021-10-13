<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/09/2017
 * Time: 15:01
 */

namespace App\Controllers\Notifications;


use App\Controllers\Integrations\Slack\_SlackWebhookController;
use App\Models\Notifications\FeatureRequest;
use App\Models\Notifications\FeatureRequestVote;
use App\Models\OauthUser;
use App\Utils\Http;
use App\Utils\PushNotifications;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Slim\Http\Response;
use Slim\Http\Request;

class _FeatureRequestNotifyController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createFeatureRequestRoute(Request $request, Response $response)
    {
        $send = $this->createFeatureRequest($request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function loadFeatureRoute(Request $request, Response $response)
    {
        $id   = $request->getAttribute('id');
        $send = $this->loadFeature($id);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function loadFeatureRequestsRoute(Request $request, Response $response)
    {
        $allowedCategories = [
            'Marketing',
            'Connect',
            'Splash Screen',
            'Reports',
            'Integration',
            'Hardware'
        ];
        $allowedStatus     = [
            'Approval',
            'Submitted',
            'Planning',
            'Progress',
            'Completed'
        ];
        $queryParams       = $request->getQueryParams();
        $usePage           = true;
        $status            = 'all';
        $category          = 'all';
        $email             = null;
        $offset            = 0;
        if (isset($queryParams['usePage'])) {
            $usePage = false;
        }
        if (isset($queryParams['status'])) {
            if (in_array($queryParams['status'], $allowedStatus)) {
                $status = $queryParams['status'];
            } else {
                return $response->withJson('NON_COMPATIBLE_STATUS', 404);
            }
        }

        if (isset($queryParams['category'])) {
            if (in_array($queryParams['category'], $allowedCategories)) {
                $category = $queryParams['category'];
            } else {
                return $response->withJson('NON_COMPATIBLE_CATEGORY', 404);
            }
        }

        if (isset($queryParams['offset'])) {
            $offset = $queryParams['offset'];
        }

        if (isset($queryParams['email'])) {
            $email = $queryParams['email'];
        }

        $send = $this->loadFeatureRequests($status, $offset, $category, $usePage, $email);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteFeatureRequestRoute(Request $request, Response $response)
    {
        $send = $this->deleteFeatureRequest($request->getQueryParams()['id']);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function submitVoteRoute(Request $request, Response $response)
    {
        $body = $request->getParsedBody();

        $send = $this->submitVote($body['email'], $request->getAttribute('id'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateFeatureRoute(Request $request, Response $response)
    {
        $send = $this->updateFeature($request->getAttribute('id'), $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function createFeatureRequest(array $body)
    {

        $oauthUser = $this->em->getRepository(OauthUser::class)->findOneBy([
            'email' => $body['email']
        ]);

        if (is_null($oauthUser)) {
            return Http::status(404, 'NOT_A_VALID_EMAIL');
        }

        $newFeature = new FeatureRequest($body['name'], $body['description'], '', 'Approval',
            $body['category']);
        $this->em->persist($newFeature);
        $this->em->flush();

        $this->submitVote($body['email'], $newFeature->id);

        $text = $body['email'] . ' has requested ' . $body['name'] . '. Time to review!';

        $slack = [
            'text' => $text
        ];

        $slackNotify = new _SlackWebhookController('feature');
        $slackNotify->slackMessage($slack);

        return Http::status(200, $newFeature->getArrayCopy());
    }

    public function loadFeature(string $id)
    {
        $feature = $this->em->getRepository(FeatureRequest::class)->findOneBy(['id' => $id]);

        if (is_null($feature)) {
            return Http::status(404, 'MISSING_FEATURE');
        }

        return Http::status(200, $feature->getArrayCopy());
    }

    public function loadFeatureRequests(string $status, $offset, string $category, bool $usePage, ?string $email)
    {
        $order = 'featureCount';
        $sort  = 'DESC';
        if ($status === 'Completed') {
            $order = 'u.completedAt';
            $sort  = 'DESC';
        }

        if ($usePage) {
            $maxResults  = 25;
        } else {
            $maxResults = 10000;
        }

        $getFeatures = $this->em->createQueryBuilder()
            ->select(
                'u.id, u.name, u.description, u.requestedAt, u.buildNumber, u.status, u.category, 
                count(v.featureId) as featureCount')
            ->from(FeatureRequest::class, 'u')
            ->leftJoin(FeatureRequestVote::class, 'v', 'WITH', 'u.id = v.featureId');
        if (!is_null($email)) {
            $getFeatures = $getFeatures->leftJoin(OauthUser::class, 'ou', 'WITH', 'v.uid = ou.uid');
        }
        if ($status !== "all" && $category === "all") {
            $getFeatures = $getFeatures->where('u.status = :status')
                ->setParameter('status', $status);
        } elseif ($category !== "all" && $status === "all") {
            $getFeatures = $getFeatures->where('u.category = :category')
                ->setParameter('category', $category);
        }

        if (!is_null($email)) {
            $getFeatures = $getFeatures->andWhere('ou.email = :email')
                ->setParameter('email', $email);
        } else {
            $getFeatures = $getFeatures->setFirstResult($offset)
                ->setMaxResults($maxResults);
        }
        $getFeatures = $getFeatures
            ->orderBy($order, $sort)
            ->groupBy('u.id');

        if (!is_null($email)) {
            $getFeatures = $getFeatures->getQuery()->getArrayResult();
            $x           = sizeof($getFeatures);
        } else {
            $results = new Paginator($getFeatures);
            $results->setUseOutputWalkers(false);
            $getFeatures = $results->getIterator()->getArrayCopy();
            $x           = $results->count();
        }


        if ($x === 0) {
            return Http::status(204, []);
        }

        $return = [
            'results'          => $getFeatures,
            'topRatedFeatures' => [],
            'has_more'         => false,
            'total'            => $x,
            'next_offset'      => $offset + $maxResults
        ];

        for ($i = 0; $i < 5; $i++) {
            $return['topRatedFeatures'][] = $getFeatures[$i];
        }


        if ($offset <= $return['total'] && count($getFeatures) !== $return['total']) {
            $return['has_more'] = true;
        }

        return Http::status(200, $return);
    }

    public function submitVote(string $email, string $featureId)
    {

        $oauthUser = $this->em->getRepository(OauthUser::class)->findOneBy([
            'email' => $email
        ]);

        if (is_null($oauthUser)) {
            return Http::status(404, 'NOT_A_VALID_EMAIL');
        }

        $alreadyVoted = $this->em->getRepository(FeatureRequestVote::class)->findOneBy([
            'featureId' => $featureId,
            'uid'       => $oauthUser->uid
        ]);

        if (is_object($alreadyVoted)) {
            return Http::status(409, 'ALREADY_VOTED');
        } else {
            $newRequest = new FeatureRequestVote($featureId, $oauthUser->uid);
            $this->em->persist($newRequest);
            $this->em->flush();
        }

        return Http::status(200);
    }

    public function deleteFeatureRequest(string $id)
    {
        $getVotes = $this->em->getRepository(FeatureRequestVote::class)->findBy([
            'featureId' => $id
        ]);

        foreach ($getVotes as $vote) {
            $this->em->remove($vote);
        }

        $getFeature = $this->em->getRepository(FeatureRequest::class)->findOneBy([
            'id' => $id
        ]);

        $this->em->remove($getFeature);

        $this->em->flush();

        return Http::status(200);
    }


    public function updateFeature(string $id, array $body)
    {
        if (!isset($body['name'], $body['description'], $body['status'], $body['category'])) {
            return Http::status(400, 'KEY_MISSING');
        }

        if (!in_array($body['status'], FeatureRequest::$allowedStatus)) {
            return Http::status(400, 'INVALID_STATUS');
        }

        if (!in_array($body['category'], FeatureRequest::$allowedCategories)) {
            return Http::status(400, 'INVALID_CATEGORY');
        }

        $find = $this->em->getRepository(FeatureRequest::class)->findOneBy([
            'id' => $id
        ]);

        if (is_object($find)) {
            foreach ($body as $key => $value) {
                if (in_array($key, FeatureRequest::$mutableKeys)) {
                    $find->$key = $value;
                }
            }
        }

        if ($body['status'] === 'Completed' || $body['status'] === 'Submitted') {

            $pushNotify = new PushNotifications($this->em);
            $notifyStat = '';
            if ($body['status'] === 'Completed') {
                $notifyStat = 'feature_completed';
            } elseif ($body['status'] === 'Submitted') {
                $notifyStat = 'feature_approved';
            }
            /*
            $pushNotify->pushNotification($find->id, 'Feature Vote', 'Feature Request', $notifyStat,
                'https://feature-vote.blackbx.io/', 'all');
            */
        }

        if ($body['status'] === 'Completed') {
            $newDateTime       = new \DateTime();
            $find->completedAt = $newDateTime->getTimestamp();
        }

        $this->em->flush();

        return Http::status(200);
    }
}
