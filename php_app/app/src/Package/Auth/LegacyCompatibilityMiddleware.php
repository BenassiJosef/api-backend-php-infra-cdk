<?php


namespace App\Package\Auth;


use App\Models\OauthUser;
use App\Package\Organisations\UserRoleChecker;
use Doctrine\DBAL\DBALException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class LegacyCompatibilityMiddleware
 * @package App\Package\Auth
 */
class LegacyCompatibilityMiddleware
{
	/**
	 * @var UserRoleChecker $userRoleChecker
	 */
	private $userRoleChecker;

	/**
	 * LegacyCompatibilityMiddleware constructor.
	 * @param UserRoleChecker $userRoleChecker
	 */
	public function __construct(UserRoleChecker $userRoleChecker)
	{
		$this->userRoleChecker = $userRoleChecker;
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param $next
	 * @return Response
	 * @throws DBALException
	 */
	public function __invoke(Request $request, Response $response, $next): Response
	{
		/** @var UserContext | null $userContext */
		$userContext = $request->getAttribute(UserContext::class);
		if ($userContext === null) {
			return $next($request, $response);
		}
		$subject = $userContext->subject();
		if ($subject !== null) {
			$subject->setAccess($this->userRoleChecker->locationSerials($subject));

			$request = $request
				->withAttribute(
					'accessUser',
					$this->asArray($subject)
				);
		}
		$actor = $userContext->actor();

		if ($actor !== null) {
			$actor->setAccess($this->userRoleChecker->locationSerials($actor));

			$request = $request
				->withAttribute(
					'user',
					$this->asArray($actor)
				)
				->withAttribute(
					'userId',
					$actor->getUid(),
				);
		}
		if ($subject === null && $actor !== null) {
			$request = $request
				->withAttribute(
					'accessUser',
					$this->asArray($actor)
				);
		}
		return $next($request, $response);
	}

	/**
	 * @param OauthUser $user
	 * @return array
	 * @throws DBALException
	 */
	private function asArray(OauthUser $user): array
	{
		return array_merge(
			$user->getArrayCopy(),
			[
				'access' => $this
					->userRoleChecker
					->locationSerials($user)
			]
		);
	}
}
