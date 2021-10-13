<?php

namespace App\Package\Auth\Access\Profile;

use App\Models\UserProfile;
use App\Package\Auth\Access\Config\AccessConfigurationMiddleware;
use App\Package\Auth\Access\Config\ProfileWhitelistMiddleware;
use App\Package\Auth\RequestValidator;
use Slim\Http\Request;

/**
 * Class ProfileRequestValidator
 * @package App\Package\Auth\Access\Profile
 */
class ProfileRequestValidator implements RequestValidator
{
    /**
     * @var string[] $selfKeywords
     */
    private static $selfKeywords = [
        'self',
        'me'
    ];

    /**
     * @var UserProfile $userProfile
     */
    private $userProfile;

    /**
     * ProfileRequestValidator constructor.
     * @param UserProfile $userProfile
     */
    public function __construct(
        UserProfile $userProfile
    ) {
        $this->userProfile = $userProfile;
    }

    /**
     * @param string $service
     * @param Request $request
     * @return bool
     */
    public function canRequest(string $service, Request $request): bool
    {
        $profileId = $this->profileIdFromRequest($request);
        if ($profileId === null) {
            return false; // no profileId, no shoes? no entry.
        }
        if ($profileId !== $this->userProfile->getId()) {
            return false; // the profile you're asking for is not yours? GTFO!
        }
        return true;
    }

    /**
     * @param Request $request
     * @return int|null
     */
    private function profileIdFromRequest(Request $request): ?int
    {
        $profileId = AccessConfigurationMiddleware::argumentFromRequest(
            $request,
            'profileId'
        );
        if (ProfileWhitelistMiddleware::isWhitelisted($request)) {
            return $this->userProfile->getId();
        }

        if ($profileId === null) {
            return null;
        }
        if (in_array($profileId, self::$selfKeywords)) {
            return $this
                ->userProfile
                ->getId();
        }
        if (is_numeric($profileId)) {
            return (int)$profileId;
        }
        return null;
    }
}