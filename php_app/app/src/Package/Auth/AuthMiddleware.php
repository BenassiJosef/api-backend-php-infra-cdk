<?php

namespace App\Package\Auth;

use App\Models\OauthUser;
use App\Package\Auth\Access\Config\AccessConfigurationMiddleware;
use App\Package\Auth\Access\User\UserRequestValidator;
use App\Package\Auth\Exceptions\ForbiddenException;
use App\Package\Auth\Exceptions\UserNotFoundException;
use App\Package\Auth\Scopes\Scope;
use App\Package\Auth\Tokens\Token;
use App\Package\Auth\Tokens\TokenSource;
use App\Package\Auth\Tokens\UserToken;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class AuthMiddleware
 * @package App\Package\Auth
 */
class AuthMiddleware
{
	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * @var TokenSource $tokenSource
	 */
	private $tokenSource;

	/**
	 * @var string $service
	 */
	private $service;

	/**
	 * AuthMiddleware constructor.
	 * @param EntityManager $entityManager
	 * @param TokenSource $tokenSource
	 * @param string $service
	 */
	public function __construct(
		EntityManager $entityManager,
		TokenSource $tokenSource,
		string $service = Scope::SERVICE_BACKEND
	) {
		$this->entityManager = $entityManager;
		$this->tokenSource   = $tokenSource;
		$this->service       = $service;
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $next
	 * @return Response
	 * @throws ForbiddenException
	 * @throws UserNotFoundException
	 */
	public function __invoke(Request $request, Response $response, $next): Response
	{
		$token = $this
			->tokenSource
			->token($request);

		if (!$token->canRequest($this->service, $request)) {
			throw new ForbiddenException();
		}

		$userContext = $this->userContext($token, $request);
		$request     = $request
			->withAttribute(
				UserSource::class,
				$userContext
			)
			->withAttribute(
				UserContext::class,
				$userContext
			);

		if ($token instanceof ProfileSource) {
			$request = $request->withAttribute(ProfileSource::class, $token);
		}

		return $next($request, $response);
	}

	/**
	 * @param Token $token
	 * @param Request $request
	 * @return UserContext
	 * @throws UserNotFoundException
	 */
	private function userContext(Token $token, Request $request): UserContext
	{
		$userContext = new UserContext();
		if ($token instanceof UserSource) {
			$userContext = $userContext->withActor($token->getUser());
		}
		$userId = UserRequestValidator::userIdFromRequest($request);
		if ($userId === null) {
			return $userContext;
		}
		if (UserRequestValidator::isSelfRequest($request)) {
			return $userContext->withSubject($userContext->actor());
		}
		return $userContext->withSubject($this->fetchUser($userId));
	}

	/**
	 * @param string $userId
	 * @return OauthUser
	 * @throws UserNotFoundException
	 */
	private function fetchUser(string $userId): OauthUser
	{
		/** @var OauthUser | null $user */
		$user = $this
			->entityManager
			->getRepository(OauthUser::class)
			->find($userId);
		if ($user === null) {
			throw new UserNotFoundException($userId);
		}
		return $user;
	}
}
