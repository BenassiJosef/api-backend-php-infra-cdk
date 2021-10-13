<?php
/**
 * Created by patrickclover on 20/08/2018 at 15:48
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations\Facebook;


use App\Controllers\Locations\Reviews\LocationReviewController;
use App\Models\Integrations\Facebook\FacebookOauth;
use App\Models\Integrations\Facebook\FacebookPages;
use App\Models\Locations\Reviews\LocationReviews;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;


class _FacebookPagesController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getPagesRoute(Request $request, Response $response)
    {
        $token = $request->getAttribute('token');

        $pages = $this->getPages($token);

        $this->em->clear();

        return $response->withJson($pages, $pages['status']);
    }


    public function getPages($accessToken)
    {
        $user = $this->em->getRepository(FacebookOauth::class)->findOneBy(['accessToken' => $accessToken]);

        if (is_null($user)) {
            return Http::status(404);
        }

        $facebookController = new _FacebookLoginController($this->em);
        $fb                 = $facebookController->init($user->appName);
        $res                = $fb->get('/me/accounts', $accessToken);

        return Http::status(200, $res->getDecodedBody()['data']);
    }

    public function updatePagesRoute(Request $request, Response $response)
    {
        $id    = $request->getAttribute('pageId');
        $token = $request->getAttribute('token');
        $body  = $request->getParsedBody();

        $pages = $this->updatePages($id, $token, $body);

        $this->em->clear();

        return $response->withJson($pages, $pages['status']);
    }

    public function updatePages(string $pageId, string $token, array $body)
    {
        if (!isset($body['serial'])) {
            return Http::status(400, 'NEED_SERIAL');
        }

        $reviewType = $this->serialToReviewId($body['serial']);

        if ($reviewType['status'] !== 200) {
            return $reviewType;
        }

        $pages = $this->getPages($token);

        $selected = [];
        foreach ($pages['message'] as $page) {
            if ($page['id'] === $pageId) {
                $selected = $page;
                break;
            }
        }


        $createPage = $this->em->getRepository(FacebookPages::class)->findOneBy([
            'pageId'   => $pageId,
            'reviewId' => $reviewType['message']['id']
        ]);

        if (is_null($createPage)) {
            $createPage = new FacebookPages();
        }
        $createPage->facebookOauthId = $this->getAccessTokenId($token)->id;
        $createPage->accessToken     = $selected['access_token'];
        $createPage->pageId          = $selected['id'];
        $createPage->name            = $selected['name'];
        $createPage->category        = $selected['category'];
        $createPage->reviewId        = $reviewType['message']['id'];

        $this->em->persist($createPage);
        $this->em->flush();

        return Http::status(200, $createPage->getArrayCopy());

    }

    public function getAccessTokenId($token)
    {
        $oauth = $this->em->getRepository(FacebookOauth::class)->findOneBy(['accessToken' => $token]);

        return $oauth;
    }

    public function serialToReviewId(string $serial)
    {
        $type = $this->em->getRepository(LocationReviews::class)->findOneBy([
            'serial'     => $serial,
            'reviewType' => 'facebook'
        ]);
        if (is_null($type)) {
            return Http::status(404);
        }

        return Http::status(200, $type->getArrayCopy());
    }

}