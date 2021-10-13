<?php


namespace App\Package\RequestUser;

use App\Models\OauthUser;
use App\Package\Auth\UserSource;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityManager;
use Slim\Http\Request;
use Exception;
use Throwable;

/**
 * Class UserProvider
 * @package App\Package\RequestUser
 */
class UserProvider
{
	/**
	 * @var EntityManager $entityManager
	 */
	private $entityManager;

	/**
	 * UserProvider constructor.
	 * @param EntityManager $entityManager
	 */
	public function __construct(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @param Request $request
	 * @return OauthUser
	 * @throws Exception
	 */
	public function getOauthUser(Request $request): ?OauthUser
	{
		try {
			/** @var UserSource | null $userSource */
			$userSource = $request->getAttribute(UserSource::class);
			if ($userSource !== null) {
				return $userSource->getUser();
			}
			if (is_null($request->getAttribute('user'))) {
				return null;
			}

			/** 
			 * @var OauthUser $user
			 */
			$user = $this
				->entityManager
				->getRepository(OauthUser::class)
				->findOneBy(
					[
						'uid' => $this->getUser($request)->getUid()
					]
				);

			return $user;
		} catch (Throwable $e) {
			return null;
		}
	}

	/**
	 * @param Request $request
	 * @return User
	 * @throws Exception
	 */
	public function getUser(Request $request): User
	{
		return User::createFromArray($request->getAttribute('user'));
	}

	/**
	 * @param Request $request
	 * @return User
	 * @throws Exception
	 */
	public function getAccessUser(Request $request): User
	{
		return User::createFromArray($request->getAttribute('accessUser'));
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager(): EntityManager
	{
		return $this->entityManager;
	}

	/**
	 * @param string $uid
	 * @return OauthUser|null
	 */
	public function getOauthUserByUid(string $uid): ?OauthUser
	{
		/** @var OauthUser|null $user */
		$user = $this->entityManager->getRepository(OauthUser::class)->findOneBy(['uid' => $uid]);
		return $user;
	}
}
