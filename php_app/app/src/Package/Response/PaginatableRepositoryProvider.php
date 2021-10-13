<?php

namespace App\Package\Response;

use Slim\Http\Request as SlimRequest;

/**
 * Interface PaginatableRepositoryProvider
 * @package App\Package\Response
 */
interface PaginatableRepositoryProvider
{
    /**
     * @param SlimRequest $request
     * @return PaginatableRepository
     */
    public function paginatableRepository(SlimRequest $request): PaginatableRepository;
}
