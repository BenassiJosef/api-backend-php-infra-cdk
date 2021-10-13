<?php

namespace App\Package\Auth\Tokens;

use App\Models\OauthAccessTokens;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * Class AccessTokenRepository
 * @package App\Package\Auth\Tokens
 */
class AccessTokenRepository implements AccessTokenSource
{
    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * AccessTokenRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $token
     * @return OauthAccessTokens|null
     */
    public function token(string $token): ?OauthAccessTokens
    {
        $queryBuilder = $this
            ->entityManager
            ->createQueryBuilder();
        $expr         = $queryBuilder->expr();
        $query        = $queryBuilder
            ->select('oat')
            ->from(OauthAccessTokens::class, 'oat')
            ->where(
                $expr->andX(
                    $expr->eq('oat.accessToken', ':token'),
                    $expr->gt('oat.expires', 'NOW()')
                )
            )
            ->setParameter('token', $token)
            ->setMaxResults(1)
            ->getQuery();
        try {
            /** @var OauthAccessTokens $oauthAccessToken */
            $oauthAccessToken = $query->getSingleResult();
        } catch (NoResultException | NonUniqueResultException $exception) {
            return null;
        }
        return $oauthAccessToken;
    }
}