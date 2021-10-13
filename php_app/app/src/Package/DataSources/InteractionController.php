<?php


namespace App\Package\DataSources;


use App\Models\DataSources\Interaction;
use App\Models\DataSources\InteractionProfile;
use App\Models\DataSources\InteractionSerial;
use App\Models\Locations\LocationSettings;
use App\Package\Organisations\LocationService;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Throwable;

/**
 * Class InteractionController
 * @package App\Package\DataSources
 */
class InteractionController
{
    /**
     * @var InteractionService $interactionService
     */
    private $interactionService;

    /**
     * @var LocationService $locationService
     */
    private $locationService;

    /**
     * @var string $redirectHost
     */
    private $redirectHost;

    /**
     * InteractionController constructor.
     * @param InteractionService $interactionService
     * @param LocationService $locationService
     * @param string $redirectHost
     */
    public function __construct(
        InteractionService $interactionService,
        LocationService $locationService,
        string $redirectHost = "https://my.stampede.ai/checkout"
    ) {
        $this->interactionService = $interactionService;
        $this->locationService    = $locationService;
        $this->redirectHost       = $redirectHost;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function endInteraction(Request $request, Response $response): Response
    {
        $interactionId = $request->getAttribute('interactionId');
        try {
            $interaction = $this->interactionService->endInteractionFromStringId($interactionId);
        } catch (InteractionNotFoundException $notFoundException) {
            return $response->withRedirect($this->errorRedirectUrl('not-found'));
        } catch (InteractionAlreadyEndedException $exception) {
            return $response->withRedirect($this->errorRedirectUrl('already-ended'));
        } catch (Throwable $throwable) {
            return $response->withRedirect($this->errorRedirectUrl('other'));
        }
        return $response->withRedirect($this->successRedirectUrl($interaction));
    }

    private function errorRedirectUrl(string $message): UriInterface
    {
        return $this
            ->baseUri()
            ->withQuery(
                http_build_query(
                    [
                        'valid' => 'false',
                        'error' => $message,
                    ]
                )
            );
    }

    private function successRedirectUrl(Interaction $interaction): UriInterface
    {
        $profileId        = from($interaction->getProfiles())
            ->select(
                function (InteractionProfile $interactionProfile): int {
                    return $interactionProfile->getProfileId();
                }
            )
            ->first();
        $serial           = from($interaction->getSerials())
            ->select(
                function (InteractionSerial $interactionSerial): string {
                    return $interactionSerial->getSerial();
                }
            )
            ->first();
        $locationSettings = $this->locationService->getLocationBySerial($serial);
        return $this
            ->baseUri()
            ->withQuery(
                http_build_query(
                    [
                        'link'             => $locationSettings->getUrl(),
                        'stmpd_profile_id' => $profileId,
                        'alias'            => $locationSettings->getAlias() ?? 'stampede',
                        'valid'            => 'true',
                    ]
                )
            );
    }

    private function baseUri(): UriInterface
    {
        return new Uri($this->redirectHost);
    }
}