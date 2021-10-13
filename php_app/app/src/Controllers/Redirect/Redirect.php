<?php


namespace App\Controllers\Redirect;


use App\Controllers\WebTracker\WebTrackingController;
use App\Package\DataSources\ProfileInteractionFactory;
use App\Package\Organisations\OrganizationProvider;
use App\Package\WebTracking\Tracking;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;

class Redirect
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ProfileInteractionFactory $interaction
     */
    private $interaction;

    public function __construct(EntityManager $em, ProfileInteractionFactory $profileInteractionFactory)
    {
        $this->em = $em;
        $this->interaction = $profileInteractionFactory;
    }

    function doRedirect(Request $request, Response $response): Response
    {
        $profileId = $request->getQueryParam('profileId');
        $webTracking = new Tracking($this->em, $this->interaction);
        $cookie = $webTracking->getCookie($request);
        $url = $request->getQueryParam('url');
        $parts = parse_url($url);
        parse_str($parts['query'], $query);
        $partsArray = array_merge($query, ['stmpd_profile_id' => $profileId]);
        $returnParams = http_build_query($partsArray);

        $fullPath = $parts['scheme'] . "://" . $parts['host'] .  $parts['path'];

        if ($profileId) {
            $response = $webTracking->handleProfileCookie($response, $cookie, $profileId);
        }

        return $response
            ->withStatus(302)
            ->withHeader('Location', $fullPath . '?' . $returnParams);
    }
}
