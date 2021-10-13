<?php
/**
 * Created by jamieaitken on 06/12/2017 at 16:20
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Marketing\Template;

use App\Models\Marketing\Template;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _BaseTemplateController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getAllBaseRoute(Request $request, Response $response)
    {
        $send = $this->getAllBase();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getTemplateRoute(Request $request, Response $response)
    {
        $send = $this->getTemplate($request->getAttribute('id'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getAllBase()
    {
        $templates = $this->em->createQueryBuilder()
            ->select('u.id, u.name, u.type')
            ->from(Template::class, 'u')
            ->getQuery()
            ->getArrayResult();

        return Http::status(200, $templates);
    }

    public function getTemplate(string $id)
    {
        $template = $this->em->createQueryBuilder()
            ->select('u.id, u.name, u.type, u.content')
            ->from(Template::class, 'u')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        return Http::status(200, $template);
    }
}