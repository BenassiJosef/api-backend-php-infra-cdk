<?php

/**
 * Created by jamieaitken on 17/11/2017 at 17:12
 * Copyright © 2017 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Marketing\Gallery;

use App\Controllers\Integrations\S3\S3;
use App\Controllers\Integrations\Uploads\_UploadsController;
use App\Models\Locations\LocationSettings;
use App\Models\MarketingCampaigns;
use App\Models\Members\Gallery;
use App\Models\Organization;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Pagination\PaginatedResponse;
use App\Utils\Http;
use App\Utils\Strings;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Monolog\Logger;
use Slim\Http\Response;
use Slim\Http\Request;
use Doctrine\ORM\Query\Expr\OrderBy;

class _GalleryController
{

    protected $em;
    private $s3;

    /**
     * @var OrganizationProvider
     */
    private $organizationProvider;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * _GalleryController constructor.
     * @param EntityManager $em
     * @param Logger $logger
     * @param OrganizationProvider|null $organizationProvider
     */
    public function __construct(EntityManager $em, Logger $logger, OrganizationProvider $organizationProvider = null)
    {
        $this->em = $em;
        $this->s3 = new S3('', '');
        if ($organizationProvider === null) {
            $organizationProvider = new OrganizationProvider($em);
        }
        $this->organizationProvider = $organizationProvider;
        $this->logger = $logger;
    }

    public function createGalleryImageRoute(Request $request, Response $response)
    {

        $params = $request->getParsedBody();

        $formatFor = null;

        if (array_key_exists('formatFor', $params)) {
            $formatFor = $params['formatFor'];
        }

        $send = $this->createGalleryImage(
            $this->organizationProvider->organizationForRequest($request),
            $params['kind'],
            $params['path'],
            $_FILES,
            $formatFor
        );

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getImages(Request $request, Response $response)
    {


        $organizationId = $request->getAttribute('orgId');
        $limit = (int) $request->getQueryParam('limit', 10);
        $offset = (int) $request->getQueryParam('offset', 0);
        $kind =  $request->getQueryParam('kind', null);
        $path =  $request->getQueryParam('path', null);

        $queryBuilder = $this
            ->em
            ->createQueryBuilder();

        $expr = $queryBuilder->expr();

        $query = $queryBuilder
            ->select('c')
            ->from(Gallery::class, 'c')
            ->where($expr->eq('c.organizationId', ':organizationId'))
            ->setParameter('organizationId', $organizationId)
            ->andWhere('c.deleted = :deleted')
            ->setParameter('deleted', false);

        if (!is_null($kind)) {
            $query = $query
                ->andWhere($expr->eq('c.kind', ':kind'))
                ->setParameter('kind', $kind);
        };

        if (!is_null($path)) {
            $query = $query
                ->andWhere($expr->eq('c.path', ':path'))
                ->setParameter('path', $path);
        };

        $query =  $query->orderBy(new OrderBy('c.createdAt', 'DESC'))
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery();

        $resp = new PaginatedResponse($query);

        return $response->withJson($resp);
    }

    public function getAllImagesRoute(Request $request, Response $response)
    {
        $offset = 0;
        $kind   = '';
        $path   = '';
        $params = $request->getQueryParams();

        if (array_key_exists('offset', $params)) {
            $offset = (int)$params['offset'];
        }

        if (array_key_exists('kind', $params)) {
            $kind = $params['kind'];
        }

        if (array_key_exists('path', $params)) {
            $path = $params['path'];
        }

        $send = $this->getAllImages($this->organizationProvider->organizationForRequest($request), $offset, $kind, $path);

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteImageRoute(Request $request, Response $response)
    {
        $send = $this->deleteImage($this->organizationProvider->organizationForRequest($request), $request->getAttribute('id'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    /**
     * @param array $user
     * @param string $kind
     * @param string $path
     * @param array $files
     * @param null $formatFor
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */

    public function createGalleryImage(Organization $organization, string $kind, string $path, array $files, $formatFor = null)
    {
        $failedToUpload = [];
        $newResize      = new _UploadsController();
        $newGalleryFile = null;
        $details = json_encode($files);
        $this->logger->debug("Creating gallery files {$details}");
        foreach ($files as $name => $file) {
            try {
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $failedToUpload[] = $file['name'];
                }

                $type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                $guid = Strings::idGenerator('fle');
                $key  = $organization->getOwnerId()->toString() . '/' . $kind . '/' . $path . '/' . $guid . '.' . $type;

                $this->logger->debug("Will upload file {$file['tmp_name']} to {$key}");

                if (!is_null($formatFor)) {
                    $files[$name] = $newResize->resizeImageFile($file, $formatFor);
                }

                $upload = $this->s3->upload(
                    $key,
                    'resource',
                    $file['tmp_name'],
                    'public-read',
                    []
                );
            } catch (\Exception $ex) {
                $this->logger->error("Error uploading file {$file['tmp_name']}, {$ex->getMessage()}");
                newrelic_notice_error("Error uploading file {$file['tmp_name']}", $ex);
            }
            $this->logger->debug("Uploaded file to {$upload}");


            $newGalleryFile = new Gallery($organization, $upload, $kind, $path);
            $this->em->persist($newGalleryFile);
        }

        $this->em->flush();

        if (sizeof($failedToUpload) > 0) {
            if (sizeof($failedToUpload) < sizeof($files)) {
                return Http::status(206, $failedToUpload);
            } elseif (sizeof($failedToUpload) === sizeof($files)) {
                return Http::status(400, 'FAILED_TO_UPLOAD_ALL_FILES');
            }
        }

        return Http::status(200, $newGalleryFile->jsonSerialize());
    }

    public function get()
    {
        return Http::status(200);
    }

    /**
     * @param array $user
     * @param int $offset
     * @param string $kind
     * @param string $path
     * @return array
     */

    public function getAllImages(Organization $organization, int $offset, string $kind, string $path)
    {
        $maxResults = 50;


        if (empty($path) && empty($kind)) {
            /**
             * On first Load list kinds
             */
            $sql = 'DISTINCT u.kind, u.url';
        } else {
            if (!empty($path) && empty($kind)) {
                /**
                 * If kind is set then user is now looking at directories
                 * Currently only marketing / location
                 */
                $sql = 'DISTINCT u.path, u.url, u.id';
            } else {
                if (empty($path) && !empty($kind)) {
                    /**
                     * if path is set but the kind isnt then we are 2 levels deep
                     */
                    $sql = 'u.url, u.path';
                } else {
                    /**
                     * both kind and path exist, the Gallery id will be needed
                     */
                    $sql = 'u.url, u.path, u.id';
                }
            }
        }

        $images = $this->em->createQueryBuilder()
            ->select($sql)
            ->from(Gallery::class, 'u')
            ->where('u.organizationId = :organizationId')
            ->andWhere('u.deleted = :deleted')
            ->setParameter('organizationId', $organization->getId())
            ->setParameter('deleted', false);

        if (!empty($kind)) {
            $images = $images->andWhere('u.kind = :kind')
                ->setParameter('kind', $kind);
        }
        if (!empty($path)) {
            $images = $images->andWhere('u.path = :path')
                ->setParameter('path', $path);
        }

        if (empty($kind) && empty($path)) {
            $images = $images->addGroupBy('u.kind');
        }

        if (!empty($kind) && empty($path)) {
            $images = $images->addGroupBy('u.path');
        }

        if (!empty($kind) && !empty($path)) {
            $images = $images->addGroupBy('u.url');
        }

        $images = $images->orderBy('u.id', 'DESC');

        $images->setFirstResult($offset)
            ->setMaxResults($maxResults);

        $results = new Paginator($images);
        $results->setUseOutputWalkers(false);

        $page = $results->getIterator()->getArrayCopy();

        if (empty($page)) {
            return Http::status(204);
        }

        if (!empty($kind)) {
            $array = [];
            foreach ($page as $key => $value) {
                if (!array_key_exists($value['path'], $array)) {
                    $array[] = $value['path'];
                }
            }

            $aliasQuery = $this->em->createQueryBuilder()
                ->select('u.alias, u.serial')
                ->from(LocationSettings::class, 'u')
                ->where('u.serial IN (:serial)')
                ->setParameter('serial', $array)
                ->getQuery()
                ->getArrayResult();

            foreach ($aliasQuery as $key => $value) {
                foreach ($page as $k => $v) {
                    if ($v['path'] === $value['serial']) {
                        $page[$k]['name'] = $value['alias'];
                    }
                }
            }

            $marketingCampaignNameQuery = $this->em->createQueryBuilder()
                ->select('u.name, u.id')
                ->from(MarketingCampaigns::class, 'u')
                ->where('u.id IN (:campaignIds)')
                ->setParameter('campaignIds', $array)
                ->getQuery()
                ->getArrayResult();

            foreach ($marketingCampaignNameQuery as $key => $value) {
                foreach ($page as $k => $v) {
                    if ($v['path'] === $value['id']) {
                        $page[$k]['name'] = $value['name'];
                    }
                }
            }
        }

        $return = [
            'has_more'    => false,
            'total'       => count($page),
            'next_offset' => $offset + $maxResults,
            'directory'   => []
        ];

        if ($return['total'] < $offset) {
            $return['has_more'] = true;
        }

        $return['directory'] = $page;

        return Http::status(200, $return);
    }

    /**
     * @param Organization $organization
     * @param string $id
     * @return array
     */

    public function deleteImage(Organization $organization, int $id)
    {
        $execute = $this->em->createQueryBuilder()
            ->update(Gallery::class, 'u')
            ->set('u.deleted', true)
            ->where('u.id = :id')
            ->andWhere('u.organizationId = :organizationId')
            ->setParameter('id', $id)
            ->setParameter('organizationId', $organization->getId())
            ->getQuery()
            ->execute();
        if ($execute === 1) {
            return Http::status(200, ['id' => $id]);
        }

        return Http::status(404, 'FAILED_TO_DELETE_IMAGE');
    }
}
