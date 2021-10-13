<?php


namespace App\Package\WebTracking;


use App\Models\UserProfile;
use App\Models\WebTracking\Website;
use App\Models\WebTracking\WebsiteEvent;
use App\Models\WebTracking\WebsiteProfileCookies;
use App\Package\DataSources\InteractionRequest;
use App\Package\DataSources\ProfileInteractionFactory;
use App\Package\Organisations\OrganizationProvider;
use App\Utils\Http;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Slim\Http\Request;
use Slim\Http\Response;

class Tracking
{

    /**
     * @var EntityManager
     */
    protected $em;
    /**
     * @var OrganizationProvider
     */
    protected $organisationProvider;


    /**
     * @var ProfileInteractionFactory $interaction
     */
    private $interaction;

    public function __construct(EntityManager $em, ProfileInteractionFactory $profileInteractionFactory)
    {
        $this->em = $em;
        $this->interaction = $profileInteractionFactory;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function assignProfileToCookie(Request $request, Response $response)
    {
        $cookie = $this->getCookie($request);
        $id = (int)$request->getParsedBody()['id'];

        if (!$id || !$cookie) {
            return $response->withStatus(400);
        }

        return $this->handleProfileCookie($response, $cookie, $id);
    }

    public function handleProfileCookie(Response $response, $cookie, $id): Response
    {

        $profile = $this->em
            ->getRepository(UserProfile::class)
            ->find($id);

        if ($profile === null) {
            return $response->withStatus(400);
        }

        $profileCookie = $this->getProfileCookie($id, $cookie);

        if ($profileCookie) {
            return $response->withJson($profileCookie->jsonSerialize(), 200);
        }

        $newProfileCookieCheck = $this->getCookieProfile($cookie);

        if (!$newProfileCookieCheck || $newProfileCookieCheck->getProfileId() !== $id) {

            $webCookies = new WebCookies();
            $newCookie = $webCookies->generateCookie();
            $response = $webCookies->setCookieResponse($response, $newCookie);
            $profileCookie = $this->createCookie($profile, $newCookie);

            $this->em->createQueryBuilder()
                ->update(WebsiteEvent::class, 'e')
                ->set('e.cookieId', ':newCookie')
                ->where('e.cookieId = :oldCookie')
                ->setParameter('newCookie', $newCookie)
                ->setParameter('oldCookie', $cookie)
                ->getQuery()
                ->execute();
        } else {
            $profileCookie = $this->createCookie($profile, $cookie);
        }

        return $response->withJson($profileCookie->jsonSerialize(), 200);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws Exception
     */
    public function createEvent(Request $request, Response $response)
    {

        $accountKey = $this->getKey($request);
        $cookie = $this->getCookie($request);
        $eventType = $request->getParsedBody()['event_type'];

        $res = Http::status(400, 'MISSING_URL');
        if (!$accountKey || !$cookie || !$eventType) {
            return $response->withJson($res, $res['status']);
        }

        $website = $this->getWebsite($accountKey);

        if (!$website) {
            return $response->withJson($res, $res['status']);
        }

        $pagePath = $this->getUrlPath($request, 'Origin');
        $referralPath = $this->getUrlPath($request, 'Referer');

        $websiteEvent = new WebsiteEvent($website, $cookie, $eventType, $pagePath, $referralPath);
        $this->em->persist($websiteEvent);
        $this->em->flush();

        $this->incrememtVisits($cookie, $website);

        return $response->withJson($websiteEvent->jsonSerialize(), 200);
    }


    /**
     * @param Request $request
     * @param string $headerKey
     * @return string
     */
    protected function getUrlPath(Request $request, string $headerKey): string
    {
        $url = '';
        if (!empty($request->getHeader($headerKey))) {
            $url = $this->remove_http($request->getHeader($headerKey)[0]);
        }

        return $url;
    }


    /**
     * @param string $id
     * @return Website|object|null
     */
    public function getWebsite(string $id): ?Website
    {
        return $this->em
            ->getRepository(Website::class)
            ->find($id);
    }

    public function getCookie(Request $request): ?string
    {
        $webCookies = new WebCookies();

        return $webCookies->getCookie($request)->getValue();
    }

    protected function getKey(Request $request): ?string
    {
        if (empty($request->getHeader('Stmpd-Key'))) {
            return null;
        }

        return $request->getHeader('Stmpd-Key')[0];
    }

    public function remove_http(?string $url): string
    {
        if (!$url) return '';
        $disallowed = [
            'http://',
            'https://'
        ];
        foreach ($disallowed as $d) {
            if (strpos($url, $d) === 0) {
                return str_replace($d, '', $url);
            }
        }

        return $url;
    }

    public function incrememtVisits($cookie, Website $website): ?WebsiteProfileCookies
    {
        $profile = $this->getCookieProfile($cookie);

        if (!$profile) {
            return null;
        }

        $thirtyMinsAgo = new DateTime();
        $thirtyMinsAgo->modify('-30 minutes');

        if ($thirtyMinsAgo > $profile->getLastvisitAt() || $profile->getLastvisitAt() === $profile->getCreatedAt()) {
            $this->createInteraction($profile, $website);
        }

        $profile->setVisits($profile->getVisits() + 1);
        $this->em->persist($profile);
        $this->em->flush();

        return $profile;
    }

    public function createInteraction(WebsiteProfileCookies $profile, Website $website)
    {
        $dataSource                   = $this->interaction->getDataSource('web-visit');
        $profileInteraction = $this->interaction
            ->makeProfileInteraction(
                new InteractionRequest(
                    $website->getOrganization(),
                    $dataSource,
                    [],
                    0
                )
            );

        $profileInteraction->saveProfileIds([$profile->getProfileId()]);
    }

    public function getProfileCookie(int $profileId, string $cookie)
    {
        return $this->em->getRepository(WebsiteProfileCookies::class)->findOneBy([
            'profileId' => $profileId,
            'cookieId' => $cookie
        ]);
    }

    /**
     * @param string $cookie
     * @return WebsiteProfileCookies|null|object
     */

    public function getCookieProfile(string $cookie): ?WebsiteProfileCookies
    {
        return $this->em->getRepository(WebsiteProfileCookies::class)->findOneBy([
            'cookieId' => $cookie
        ]);
    }

    /**
     * @param UserProfile $profile
     * @param string $cookie
     * @return WebsiteProfileCookies
     * @throws ORMException
     * @throws OptimisticLockException
     */

    public function createCookie(UserProfile $profile, string $cookie)
    {
        $profileCookie = new WebsiteProfileCookies($profile, $cookie);
        $this->em->persist($profileCookie);
        $this->em->flush();

        return $profileCookie;
    }
}
