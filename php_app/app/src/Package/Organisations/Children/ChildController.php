<?php


namespace App\Package\Organisations\Children;


use App\Package\Pagination\RepositoryPaginatedResponse;
use App\Package\Response\PaginatableRepository;
use Slim\Http\Request;
use Slim\Http\Response;

class ChildController
{
    /**
     * @var ChildRepositoryFactory $childRepositoryFactory
     */
    private $childRepositoryFactory;

    /**
     * ChildController constructor.
     * @param ChildRepositoryFactory $childRepositoryFactory
     */
    public function __construct(
        ChildRepositoryFactory $childRepositoryFactory
    ) {
        $this->childRepositoryFactory = $childRepositoryFactory;
    }

    public function children(Request $request, Response $response): Response
    {
        return $response->withJson(
            RepositoryPaginatedResponse::fromRequestAndRepository(
                $request,
                $this->childRepositoryFactory->paginatableRepository($request)
            )
        );
    }
}