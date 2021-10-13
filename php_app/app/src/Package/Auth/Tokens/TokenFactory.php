<?php

namespace App\Package\Auth\Tokens;

use App\Models\OauthAccessTokens;
use App\Package\Auth\Access\User\UserRequestValidatorSource;
use App\Package\Auth\Tokens\Exceptions\UnauthorizedException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Slim\Http\Request;

/**
 * Class TokenFactory
 * @package App\Package\Auth\Tokens
 */
class TokenFactory implements TokenSource
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var AccessTokenSource $accessTokenSource
     */
    private $accessTokenSource;

    /**
     * @var UserRequestValidatorSource $userRequestValidatorFactory
     */
    private $userRequestValidatorFactory;

    /**
     * @var string $authHeaderName
     */
    private $authHeaderName;

    /**
     * TokenFactory constructor.
     * @param EntityManager $entityManager
     * @param AccessTokenSource $accessTokenSource
     * @param UserRequestValidatorSource $userRequestValidatorFactory
     * @param string $authHeaderName
     */
    public function __construct(
        EntityManager $entityManager,
        AccessTokenSource $accessTokenSource,
        UserRequestValidatorSource $userRequestValidatorFactory,
        string $authHeaderName = 'Authorization'
    ) {
        $this->entityManager               = $entityManager;
        $this->accessTokenSource           = $accessTokenSource;
        $this->userRequestValidatorFactory = $userRequestValidatorFactory;
        $this->authHeaderName              = $authHeaderName;
    }

    /**
     * @param Request $request
     * @return Token
     * @throws UnauthorizedException
     */
    public function token(Request $request): Token
    {
        $oauthAccessToken = $this->oauthAccessTokenFromRequest($request);
        if ($oauthAccessToken === null) {
            throw new UnauthorizedException();
        }
        $userId = $this->userIdFromAccessToken($oauthAccessToken);
        switch (gettype($userId)) {
            case 'string':
                return UserToken::fromOauthAccessToken(
                    $this->entityManager,
                    $this->userRequestValidatorFactory,
                    $oauthAccessToken
                );
            case 'integer':
                return ProfileToken::fromOauthAccessToken(
                    $this->entityManager,
                    $oauthAccessToken
                );
            case 'NULL':
            default:
                return BaseToken::fromOauthAccessToken($oauthAccessToken);
        }
    }

    /**
     * @param OauthAccessTokens $accessToken
     * @return int|string|null
     */
    private function userIdFromAccessToken(OauthAccessTokens $accessToken)
    {
        $userId = $accessToken->getUserId();
        if ($userId === null) {
            return null;
        }
        if (is_numeric($userId)) {
            return (int)$userId;
        }
        return $userId;
    }

    /**
     * @param Request $request
     * @return string
     * @throws UnauthorizedException
     */
    private function tokenStringFromRequest(Request $request): string
    {
        if (!$request->hasHeader($this->authHeaderName)) {
            throw new UnauthorizedException();
        }
        $headerLine  = $request->getHeaderLine($this->authHeaderName);
        $headerParts = string($headerLine)
            ->explode(' ', 2);
        if (count($headerParts) !== 2) {
            throw new UnauthorizedException();
        }
        [, $token] = $headerParts;
        return $token;
    }

    /**
     * @param Request $request
     * @return OauthAccessTokens|null
     * @throws UnauthorizedException
     */
    private function oauthAccessTokenFromRequest(Request $request): ?OauthAccessTokens
    {
        return $this
            ->accessTokenSource
            ->token(
                $this
                    ->tokenStringFromRequest($request)
            );
    }
}