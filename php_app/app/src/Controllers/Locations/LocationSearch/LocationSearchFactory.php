<?php
/**
 * Created by jamieaitken on 22/01/2019 at 13:37
 * Copyright © 2019 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Locations\LocationSearch;


use App\Controllers\Locations\LocationSearch\AccessibleLocationSearcher;
use App\Controllers\Locations\LocationSearch\AccessibleMarketLocationSearcher;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;

class LocationSearchFactory
{

    const MARKETING = 'marketing';
    const LOCATION = 'location';

    private $params;
    private $context;
    private $em;
    private $user;
    protected $connectCache;

    public function __construct(string $context, array $queryParams, array $user, EntityManager $em)
    {
        $this->params       = $queryParams;
        $this->context      = strtolower($context);
        $this->em           = $em;
        $this->user         = $user;
        $this->connectCache = new CacheEngine(getenv('CONNECT_REDIS'));

    }

    public function createInstance()
    {

        $create = null;

        if ($this->context === LocationSearchFactory::MARKETING) {
            $create = new AccessibleMarketLocationSearcher($this->em);
        } elseif ($this->context === LocationSearchFactory::LOCATION) {
            $create = new AccessibleLocationSearcher($this->em);
        }

        $create->setSerials($this->user['access']);
        $create->isSearchingTown($this->params);
        $create->isSearchingAddress($this->params);
        $create->isSearchingPostCode($this->params);
        $create->isSearchingBusinessType($this->params);
        $create->isSearchingLocationType($this->params);
        $create->isOffsetPresent($this->params);

        $cacheFetch = $this->connectCache->fetch($this->user['uid'] . ':' . $this->context . ':accessibleLocations');


        if (!is_bool($cacheFetch) && is_null($create->getOffset())) {
            return Http::status(200, $cacheFetch);
        }

        $search = $create->prepareBaseStatement();

        if (is_null($create->getOffset())) {
            $search = $search->getQuery()->getArrayResult();
        } else {
            $search = $search->setFirstResult($create->getOffset())
                ->setMaxResults(50);


            $results = new Paginator($search);
            $results->setUseOutputWalkers(false);

            $search = $results->getIterator()->getArrayCopy();

            $return = [
                'locations'   => $search,
                'has_more'    => false,
                'total'       => count($results),
                'next_offset' => $create->getOffset() + 50
            ];

            if ($create->getOffset() <= $return['total'] && count($search) !== $return['total']) {
                $return['has_more'] = true;
            }
        }

        if (empty($search)) {
            return Http::status(200, []);
        }

        $cleanUpLocations = $this->cleanLocationAlias($search);

        if (is_null($create->getOffset()) && is_null($create->getLocationType())) {
            $this->connectCache->save($this->user['uid'] . ':' . $this->context . ':accessibleLocations',
                $cleanUpLocations);
        }


        if (!is_null($create->getOffset())) {
            $return['locations'] = $cleanUpLocations;

            return Http::status(200, $return);
        }

        return Http::status(200, $cleanUpLocations);
    }

    private function cleanLocationAlias(array $locations)
    {
        foreach ($locations as $key => $location) {
            if (!isset($location['locationType'])) {
                $locations[$key]['locationType'] = false;
            }

            if (is_null($location['alias'])) {
                continue;
            }

            if (strlen($location['alias']) < 24) {
                continue;
            }

            $locations[$key]['alias'] = substr($location['alias'], 0, 20) . '...';
        }

        return $locations;
    }

}