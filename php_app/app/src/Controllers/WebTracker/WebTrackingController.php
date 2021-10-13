<?php
declare(strict_types=1);

namespace App\Controllers\WebTracker;

use App\Models\UserProfile;
use App\Models\WebTracking\Website;
use App\Package\WebTracking\WebCookies;
use Doctrine\ORM\EntityManager;
use  \App\Package\Organisations\OrganizationProvider;
use \App\Utils\Http;
use App\Models\WebTracking;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Slim\Http\Request;
use Slim\Http\Response;

class WebTrackingController
{
    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var OrganizationProvider
     */
    protected $organisationProvider;

    public function __construct(EntityManager $em)
    {
        $this->em                   = $em;
        $this->organisationProvider = new OrganizationProvider($em);
    }

    public function getCookieRequest(Request $request, Response $response)
    {
        $cookie = $this->getCookie($request);

        return $response->withJson($cookie, 200);
    }



    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function listWebsites(Request $request, Response $response)
    {

        $org      = $request->getAttribute('orgId');
        $websites = $this
            ->em
            ->getRepository(Website::class)
            ->findBy(
                [
                    'organizationId' => $org
                ]
            );

        if (!$websites) {
            return $response->withStatus(404);
        }

        $websitesArray = [];
        foreach ($websites as $website) {
            $websitesArray[] = $website->jsonSerialize();
        }

        $res = Http::status(200, $websitesArray);

        return $response->withJson($res, $res['status']);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function createWebsite(Request $request, Response $response)
    {
        $org = $this->organisationProvider->organizationForRequest($request);
        $url = $request->getParsedBody()['url'];
        if (empty($url)) {
            return $response->withStatus(400);
        }
        $website = new Website($org, $url);
        $this->em->persist($website);
        $this->em->flush();

        $res = Http::status(200, $website->jsonSerialize());

        return $response->withJson($res, $res['status']);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function updateWebsite(Request $request, Response $response)
    {

        $id  = $request->getAttribute('id');
        $url = $request->getParsedBody()['url'];
        if (empty($url)) {
            return $response->withStatus(400);
        }
        $website = $this->getWebsite($id);

        if (empty($website)) {
            return $response->withStatus(400);
        }

        $website->setUrl($url);
        $this->em->persist($website);
        $this->em->flush();

        $res = Http::status(200, $website->jsonSerialize());

        return $response->withJson($res, $res['status']);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     */

    public function createWebsiteFromOrg(Request $request, Response $response)
    {
        if (!$request->getAttribute('orgId')) {
            return $response->withStatus(400);
        }

        $org     = $this->organisationProvider->organizationForRequest($request);
        $website = $this
            ->em
            ->getRepository(Website::class)
            ->findOneBy([
                'organizationId' => $org->getId()
            ]);

        if (is_null($website)) {
            $url     = 'internal.stampede.ai';
            $website = new Website($org, $url);
            $this->em->persist($website);
            $this->em->flush();
        }

        $res = Http::status(200, $website->jsonSerialize());

        return $response->withJson($res, $res['status']);
    }


    public function getLiveEvents(Request $request, Response $response)
    {
        $id = $request->getAttribute('id');

        $start = new \DateTime();
        $end   = new \DateTime();
        $start->modify('-30 minutes');
        //$start->modify('-10 days');

        $events = $this->em->createQueryBuilder()
            ->select('e.createdAt, e.referralPath, up.email, up.first, up.last, up.id')
            ->from(WebTracking\WebsiteEvent::class, 'e')
            ->leftJoin(WebTracking\WebsiteProfileCookies::class, 'pc', 'WITH', 'pc.cookieId = e.cookieId')
            ->leftJoin(UserProfile::class, 'up', 'WITH', 'up.id = pc.profileId')
            ->where('e.websiteId = :websiteId')
            ->setParameter('websiteId', $id)
            ->andWhere('e.eventType = :type')
            ->setParameter('type', 'page_load')
            ->andWhere('e.createdAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('e.createdAt', 'DESC')
            ->groupBy('e.cookieId')
            ->getQuery()
            ->getArrayResult();

        $eventsArr = [];
        foreach ($events as $event) {
            $eventsArr[] = [
                'referral_path' => $event['referralPath'],
                'createdAt'     => $event['createdAt'],
                'profile'       => [
                    'email' => $event['email'],
                    'id'    => (int)$event['id'],
                    'first' => $event['first'],
                    'last'  => $event['last'],
                ]
            ];
        }

        $res = Http::status(200, $eventsArr);

        return $response->withJson($res, $res['status']);

    }

    public function getEvents(Request $request, Response $response)
    {
        $id     = $request->getAttribute('id');
        $events = $this->em->createQueryBuilder()
            ->select(WebTracking\WebsiteEvent::class, 'e')
            ->where('websiteId = :websiteId')
            ->setParameter('websiteId', $id)
            ->orderBy('createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        if (!$events) {
            return $response->withStatus(404);
        }

        $eventsArray = [];
        foreach ($events as $event) {
            $eventsArray[] = $event->jsonSerialize();
        }

        $res = Http::status(200, $eventsArray);

        return $response->withJson($res, $res['status']);

    }

    /**
     * @param string $id
     * @return WebTracking\Website
     */
    public function getWebsite(string $id): ?WebTracking\Website
    {
        return $this->em
            ->getRepository(WebTracking\Website::class)
            ->find($id);
    }

    /**
     * @param string $profiileId
     * @return WebTracking\WebsiteProfileCookies|null
     */

    public function getProfileCookie(int $profileId): ?WebTracking\WebsiteProfileCookies
    {
        return $this->em->getRepository(WebTracking\WebsiteProfileCookies::class)->findOneBy([
            'profileId' => $profileId
        ]);
    }

    /**
     * @param string $cookie
     * @return WebTracking\WebsiteProfileCookies|null
     */

    public function getCookieProfile(string $cookie): ?WebTracking\WebsiteProfileCookies
    {
        return $this->em->getRepository(WebTracking\WebsiteProfileCookies::class)->findOneBy([
            'cookieId' => $cookie
        ]);
    }



}