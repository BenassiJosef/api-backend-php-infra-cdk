<?php
/**
 * Created by jamieaitken on 15/04/2018 at 14:21
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Marketing\Report;

use App\Models\Marketing\ShortUrl;
use App\Models\Marketing\ShortUrlEvent;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class URLShortenerEventController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createRoute(Request $request, Response $response)
    {
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = @$_SERVER['REMOTE_ADDR'];
        $result  = ['country' => '', 'city' => ''];
        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }
        $ip_data = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
        if ($ip_data && !is_null($ip_data->geoplugin_countryName)) {
            $result['country'] = $ip_data->geoplugin_countryCode;
            $result['city']    = $ip_data->geoplugin_city;
        }

        $send = $this->create($result, $request->getAttribute('shortUrl'));

        if ($send['status'] === 302) {
            return $response->withStatus(302)->withHeader('Location', $send['message']);
        }

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getAnalyticsRoute(Request $request, Response $response)
    {
        $send = $this->getAnalytics($request->getAttribute('shortUrl'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getAllShortLinksRoute(Request $request, Response $response)
    {
        $send = $this->getAllShortLinks($request->getAttribute('uid'));
        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateRoute(Request $request, Response $response)
    {

        $send = $this->update();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteRoute(Request $request, Response $response)
    {

        $send = $this->delete();

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function create(array $clientDetails, string $shortUrl)
    {
        $getLongUrl = $this->em->createQueryBuilder()
            ->select('u.longUrl, u.id')
            ->from(ShortUrl::class, 'u')
            ->where('u.shortUrl = :short')
            ->setParameter('short', $shortUrl)
            ->getQuery()
            ->getArrayResult();

        if (empty($getLongUrl)) {
            return Http::status(400, 'NOT_A_VALID_SHORT_URL');
        }

        $newEvent = new ShortUrlEvent($getLongUrl[0]['id'], $clientDetails['country'], $clientDetails['city']);
        $this->em->persist($newEvent);

        $this->em->flush();

        return Http::status(302, $getLongUrl[0]['longUrl']);
    }

    public function getAnalytics(string $url)
    {
        $response = [
            'cities'    => [],
            'countries' => [],
            'events'    => []
        ];


        $getEvents = $this->em->createQueryBuilder()
            ->select('u.country, u.city, u.eventCreatedAt')
            ->from(ShortUrlEvent::class, 'u')
            ->join(ShortUrl::class, 'j', 'WITH', 'u.shortUrlId = j.id')
            ->where('j.shortUrl = :short')
            ->setParameter('short', $url)
            ->getQuery()
            ->getArrayResult();

        $response['events'] = $getEvents;

        foreach ($getEvents as $key => $event) {
            if (!isset($response['cities'][$event['city']])) {
                $response['cities'][$event['city']] = [
                    'clicks' => 1
                ];
            } else {
                $response['cities'][$event['city']]['clicks'] += 1;
            }
            if (!isset($response['countries'][$event['country']])) {
                $response['countries'][$event['country']] = [
                    'clicks' => 1
                ];
            } else {
                $response['countries'][$event['country']]['clicks'] += 1;
            }
        }

        foreach ($response['cities'] as $key => $city) {
            $response['cities'][$key]['accountability'] = round($response['cities'][$key]['clicks'] / sizeof($getEvents) * 100,
                2);
        }

        foreach ($response['countries'] as $key => $country) {
            $response['countries'][$key]['accountability'] = round($response['countries'][$key]['clicks'] / sizeof($getEvents) * 100,
                2);
        }

        array_multisort(array_map(function ($element) {
            return $element;
        }, $response['cities']), SORT_DESC, $response['cities']);

        array_multisort(array_map(function ($element) {
            return $element;
        }, $response['countries']), SORT_DESC, $response['countries']);

        return Http::status(200, $response);
    }

    public function getAllShortLinks(string $user)
    {
        $response = [
            'cities'      => [],
            'countries'   => [],
            'totalClicks' => 0,
            'links'       => []
        ];

        $getLinks = $this->em->createQueryBuilder()
            ->select('u.longUrl, u.shortUrl, u.createdAt, e.country, e.city')
            ->from(ShortUrl::class, 'u')
            ->leftJoin(ShortUrlEvent::class, 'e', 'WITH', 'u.id = e.shortUrlId')
            ->where('u.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        foreach ($getLinks as $key => $link) {

            $key = array_search($link['shortUrl'], array_column($response['links'], 'shortUrl'));

            if (is_bool($key)) {
                $response['links'][] = [
                    'longUrl'   => $link['longUrl'],
                    'shortUrl'  => $link['shortUrl'],
                    'createdAt' => $link['createdAt']
                ];
            }

            if (is_null($link['country'])) {
                continue;
            }

            /**
             * Event Logic
             */

            $response['totalClicks'] += 1;

            if (!isset($response['cities'][$link['city']])) {
                $response['cities'][$link['city']] = [
                    'clicks' => 1
                ];
            } else {
                $response['cities'][$link['city']]['clicks'] += 1;
            }
            if (!isset($response['countries'][$link['country']])) {
                $response['countries'][$link['country']] = [
                    'clicks' => 1
                ];
            } else {
                $response['countries'][$link['country']]['clicks'] += 1;
            }
        }

        foreach ($response['cities'] as $key => $city) {
            $response['cities'][$key]['accountability'] = round($response['cities'][$key]['clicks'] / $response['totalClicks'] * 100,
                2);
        }

        foreach ($response['countries'] as $key => $country) {
            $response['countries'][$key]['accountability'] = round($response['countries'][$key]['clicks'] / $response['totalClicks'] * 100,
                2);
        }

        array_multisort(array_map(function ($element) {
            return $element;
        }, $response['cities']), SORT_DESC, $response['cities']);

        array_multisort(array_map(function ($element) {
            return $element;
        }, $response['countries']), SORT_DESC, $response['countries']);

        return Http::status(200, $response);
    }

    public function update()
    {
        return Http::status(200);
    }

    public function delete()
    {
        return Http::status(200);
    }
}