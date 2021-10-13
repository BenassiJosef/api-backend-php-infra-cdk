<?php


namespace App\Controllers\User;


use App\Package\RequestUser\UserProvider;
use Slim\Http\Request;
use Slim\Http\Response;
use Exception;

/**
 * Class MeController
 * @package App\Controllers\User
 */
class MeController
{
    /**
     * @var UserProvider $userProvider
     */
    private $userProvider;

    /**
     * @var MeRepository $meRepository
     */
    private $meRepository;

    /**
     * MeController constructor.
     * @param UserProvider $userProvider
     * @param MeRepository $meRepository
     */
    public function __construct(UserProvider $userProvider, MeRepository $meRepository)
    {
        $this->userProvider = $userProvider;
        $this->meRepository = $meRepository;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function getRoute(Request $request, Response $response)
    {
        $user = $this->userProvider->getOauthUser($request);
        $me = $this->meRepository->me($user);

        return $response->withJson($me, 200);
    }
}