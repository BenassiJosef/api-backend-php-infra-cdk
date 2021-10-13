<?php


namespace App\Package\Response;

use App\Package\Pagination\RepositoryPaginatedResponse;
use Slim\Http\Request as SlimRequest;
use Slim\Http\Response as SlimResponse;

/**
 * Class PaginationResponder
 * @package App\Package\Response
 */
class PaginationResponder
{
    /**
     * @param PaginatableRepositoryProvider $paginatableRepositoryProvider
     * @return static
     */
    public static function fromRepositoryProvider(
        PaginatableRepositoryProvider $paginatableRepositoryProvider
    ): self {
        return new self($paginatableRepositoryProvider);
    }

    /**
     * @var PaginatableRepositoryProvider $paginatableRepositoryProvider
     */
    private $paginatableRepositoryProvider;

    /**
     * PaginationResponder constructor.
     * @param PaginatableRepositoryProvider $paginatableRepositoryProvider
     */
    public function __construct(PaginatableRepositoryProvider $paginatableRepositoryProvider)
    {
        $this->paginatableRepositoryProvider = $paginatableRepositoryProvider;
    }

    /**
     * @param SlimRequest $request
     * @param SlimResponse $response
     * @return SlimResponse
     */
    public function respond(SlimRequest $request, SlimResponse $response): SlimResponse
    {
        return $response
            ->withJson(
                RepositoryPaginatedResponse::fromRequestAndRepository(
                    $request,
                    $this->paginatableRepository($request)
                )
            );
    }

    private function paginatableRepository(SlimRequest $request): PaginatableRepository
    {
        return $this
            ->paginatableRepositoryProvider
            ->paginatableRepository($request);
    }
}