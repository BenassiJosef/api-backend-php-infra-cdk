<?php

/**
 * Created by jamieaitken on 06/03/2019 at 11:27
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Nearly\Stories;

use App\Controllers\Integrations\S3\S3;
use App\Models\Nearly\Stories\NearlyStory;
use App\Models\Nearly\Stories\NearlyStoryPage;
use App\Models\Nearly\Stories\NearlyStoryPageActivity;
use App\Models\Nearly\Stories\NearlyStoryPageActivityAggregate;
use App\Models\Nearly\Stories\NearlyStoryPageEvent;
use App\Models\Nearly\Stories\NearlyStorySerial;
use App\Utils\CacheEngine;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\EntityManager;
use App\Utils\Http;

class NearlyStoryController
{
    protected $em;
    protected $s3;
    protected $nearlyCache;

    public function __construct(EntityManager $em)
    {
        $this->em          = $em;
        $this->s3          = new S3('nearly.online', 'eu-west-1');
        $this->nearlyCache = new CacheEngine(getenv('NEARLY_REDIS'));
    }

    public function createOrUpdateRoute(Request $request, Response $response)
    {

        $send = $this->createOrUpdate($request->getAttribute('serial'), $request->getParsedBody());

        return $response->withJson($send, $send['status']);
    }


    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get($request->getAttribute('serial'));

        return $response->withJson($send, $send['status']);
    }

    public function archivePageRoute(Request $request, Response $response)
    {
        $send = $this->archivePage($request->getAttribute('pageId'), $request->getAttribute('status'));

        return $response->withJson($send, $send['status']);
    }

    public function getS3ImagePath(string $pageId, string $serial, string $url)
    {
        $base = 'static/media/stories/';
        $img = $url;
        $tmp = explode('.', $img);
        $extension = end($tmp);
        return $base . $serial . '/' . $pageId . '.' . $extension;
    }

    public function get(string $serial, bool $fromCache = true)
    {



        $fetch = $this->em->createQueryBuilder()
            ->select('u.storyInterval, p.storyId, p.pageNumber, p.title, p.id, p.subtitle, p.style, p.imgSrc, p.linkUrl, p.linkText, u.enabled')
            ->from(NearlyStory::class, 'u')
            ->leftJoin(NearlyStoryPage::class, 'p', 'WITH', 'u.id = p.storyId')
            ->where('u.serial = :serial')
            ->andWhere('p.isArchived = :false')
            ->setParameter('serial', $serial)
            ->setParameter('false', false)
            ->orderBy('p.pageNumber', 'ASC')
            ->getQuery()
            ->getArrayResult();

        if (empty($fetch)) {
            return Http::status(204);
        }

        $returnPayload = [
            'storyId'       => $fetch[0]['storyId'],
            'storyInterval' => $fetch[0]['storyInterval'],
            'enabled'       => $fetch[0]['enabled'],
            'pages'         => []
        ];


        foreach ($fetch as $page) {
            $returnPayload['pages'][] = [
                'pageNumber' => $page['pageNumber'],
                'title'      => $page['title'],
                'subtitle'   => $page['subtitle'],
                'style'      => $page['style'],
                'imgSrc'     => $page['imgSrc'],
                'linkUrl'    => $page['linkUrl'],
                'linkText'   => $page['linkText'],
                'pageId'     => $page['id']
            ];
        }

        $this->nearlyCache->save($serial . ':stories', $returnPayload);

        return Http::status(200, $returnPayload);
    }


    public function createOrUpdate(string $serial, array $body)
    {

        if (!isset($body['pages'])) {
            return Http::status(409, 'PAGES_KEY_MISSING');
        }

        $this->nearlyCache->delete($serial . ':stories');

        $newStory = false;

        if (is_null($body['storyId'])) {
            $story = new NearlyStory($serial, $body['storyInterval']);
            $this->em->persist($story);
            $newStory         = true;
        } else {
            $story = $this->em->getRepository(NearlyStory::class)->find($body['storyId']);
        }

        /**
         *@var NearlyStoryPage[] $pages
         */
        $pages =  $this->em
            ->getRepository(NearlyStoryPage::class)->findBy(['storyId' => $story->getId(), 'isArchived' => false]);


        foreach ($body['pages'] as $pageIndex => $newPage) {
            if ($newPage['pageId']) {
                continue;
            }
            $this->createNewPage(
                $newPage,
                $story->getId(),
                $pageIndex
            );
        }

        foreach ($pages as $page) {
            $key = array_search($page->getId(), array_column($body['pages'], 'pageId'));
            if (is_bool($key)) {
                continue;
            }
            $updatePage = $body['pages'][$key];
            $page->pageNumber = $updatePage['pageNumber'];
            $page->title      = isset($updatePage['title']) ? $updatePage['title'] : null;
            $page->subtitle   = isset($updatePage['subtitle']) ? $updatePage['subtitle'] : null;
            $page->style      = $updatePage['style'];

            $page->setImageSrc($updatePage['imgSrc']);
            $page->linkUrl    = isset($updatePage['linkUrl']) ? $updatePage['linkUrl'] : null;
            $page->linkText   = isset($updatePage['linkText']) ? $updatePage['linkText'] : null;

            $this->em->persist($page);
        }


        $this->em->createQueryBuilder()
            ->update(NearlyStory::class, 'u')
            ->set('u.updatedAt', ':updatedAt')
            ->set('u.enabled', ':enabled')
            ->where('u.serial = :serial')
            ->setParameter('serial', $serial)
            ->setParameter('updatedAt', new \DateTime())
            ->setParameter('enabled', $body['enabled'])
            ->getQuery()
            ->execute();

        $this->em->flush();

        return $this->get($serial, false);
    }

    private function createNewPage(array $page, string $storyId, int $pageNumber)
    {
        $create = new NearlyStoryPage(
            $storyId,
            $pageNumber,
            isset($page['title']) ? $page['title'] : null,
            isset($page['subtitle']) ? $page['subtitle'] : null,
            $page['style'],
            $page['imgSrc'],
            isset($page['linkUrl']) ? $page['linkUrl'] : null,
            isset($page['linkText']) ? $page['linkText'] : null
        );

        $this->em->persist($create);

        return $create;
    }

    private function archivePage(string $pageId, bool $status)
    {

        $this->em->createQueryBuilder()
            ->update(NearlyStoryPage::class, 'u')
            ->set('u.isArchived', ':isArchived')
            ->where('u.id = :id')
            ->setParameter('id', $pageId)
            ->setParameter('isArchived', $status)
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->update(NearlyStoryPageActivityAggregate::class, 'u')
            ->set('u.isArchived', ':isArchived')
            ->where('u.pageId = :id')
            ->setParameter('id', $pageId)
            ->setParameter('isArchived', $status)
            ->getQuery()
            ->execute();

        $getPage = $this->em->getRepository(NearlyStoryPage::class)->findOneBy([
            'id' => $pageId
        ]);

        $getPageArray = $getPage->getArrayCopy();

        $getStory = $this->em->getRepository(NearlyStory::class)->findOneBy([
            'id' => $getPageArray['storyId']
        ]);

        $getPageArray['pageId'] = $getPageArray['id'];
        unset($getPageArray['id']);

        $this->nearlyCache->delete($getStory->serial . ':stories');

        return Http::status(200, $getPageArray);
    }
}
