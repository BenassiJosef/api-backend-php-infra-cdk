<?php

namespace App\Controllers\Branding;

use App\Controllers\Members\StandalonePartnerController;
use App\Models\Members\StandalonePartner;
use App\Models\Members\StandalonePartnerBranding;
use App\Models\NetworkAccess;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

/**
 * Created by PhpStorm.
 * User=> patrickclover
 * Date=> 12/12/2016
 * Time=> 23=>21
 */
class _BrandingController
{
    protected $em;
    protected $connectCache;

    public function __construct(EntityManager $em)
    {
        $this->em           = $em;
        $this->connectCache = new CacheEngine(getenv('CONNECT_REDIS'));
    }

    public function getMetaDataRoute(Request $request, Response $response)
    {
        $url  = $request->getQueryParams()['url'];
        $tags = @get_meta_tags(urldecode($url));
        if (is_array($tags)) {
            $tags['favicon'] = $url . '/favicon.ico';
        }

        $this->em->clear();

        return $response->withJson($tags, 200);
    }

    public function getBrandingRoute(Request $request, Response $response)
    {
        $send = $this->getBranding($request->getQueryParams());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getBranding(array $params)
    {
        if (isset($params['serial'])) {
            $re = $this->fromSerial($params['serial']);
        }

        if (isset($params['uid'])) {
            $re = $this->fromAdmin($params['uid']);
        }

        if (isset($params['domain'])) {
            $re = $this->fromSubdomain($params['domain']);
        }

        return Http::status(200, $re);
    }

    public function fromSubdomain(string $domain)
    {
        $check = $this->connectCache->fetch('branding:domains:' . $domain);
        if (!is_bool($check)) {
            return $check;
        }

        $results = $this->em->createQueryBuilder()
            ->select(
                'w.brandName, w.company, w.domain, w.name, w.phoneNo, w.policy, 
           w.terms, w.website, w.support'
            )
            ->from(NetworkAccess::class, 'a')
            ->leftJoin(StandalonePartner::class, 'q', 'WITH', 'q.uid = a.admin')
            ->leftJoin(StandalonePartnerBranding::class, 'w', 'WITH', 'q.partnerBrandingId = w.id')
            ->where('w.domain = :domain')
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getArrayResult();

        if (empty($results) || is_null($results[0]['domain'])) {
            $parser = $this->defaults();
        } else {
            $parser = $this->parseBrand(['branding' => $results[0]]);
        }

        $this->connectCache->save('branding:domains:' . $domain, $parser);

        return $parser;
    }

    public function fromAdmin(string $uid)
    {

        $check = $this->connectCache->fetch('branding:admins:' . $uid);
        if (!is_bool($check)) {
            return $check;
        }

        $parser = $this->defaults();

        $this->connectCache->save('branding:admins:' . $uid, $parser);

        return $parser;
    }

    public function fromSerial(string $serial)
    {
        $check = $this->connectCache->fetch('branding:serials:' . $serial);
        if (!is_bool($check)) {
            return $check;
        }

        $results = $this->em->createQueryBuilder()
            ->select(
                'w.brandName, w.company, w.domain, w.name, w.phoneNo, w.policy, 
           w.terms, w.website, w.support'
            )
            ->from(NetworkAccess::class, 'a')
            ->leftJoin(StandalonePartner::class, 'q', 'WITH', 'q.uid = a.admin')
            ->leftJoin(StandalonePartnerBranding::class, 'w', 'WITH', 'q.partnerBrandingId = w.id')
            ->where('a.serial = :serial')
            ->setParameter('serial', $serial);

        $results = $results
            ->getQuery()
            ->getArrayResult();

        if (empty($results) || is_null($results[0]['domain'])) {
            $parser = $this->defaults();
        } else {
            $parser = $this->parseBrand(['branding' => $results[0]]);
        }

        $this->connectCache->save('branding:serials:' . $serial, $parser);

        return $parser;
    }

    public function parseBrand($results)
    {

        $brand = $this->defaults();

        if (is_null($results)) {
            return $brand;
        }

        if (!isset($results['branding'])) {
            return $brand;
        }

        $partnerBranding   = $results['branding'];
        $brand['hasBrand'] = true;
        foreach ($partnerBranding as $key => $value) {
            $brand[$key] = $value;
            if ($key === 'domain') {
                $brand[$key] = 'https://' . $value . '.getconnecting.io/';
            }
        }


        return $brand;
    }

    public function defaults()
    {
        return [
            'domain'       => 'http://product.stampede.ai/',
            'name'         => 'Stampede',
            'brandName'    => '<strong>Stampede</strong>',
            'company'      => 'Stampede AI Ltd',
            'phoneNo'      => '0131 214 1102',
            'terms'        => 'https://stampede.ai/company/terms-of-use/',
            'policy'       => 'https://stampede.ai/company/privacy-policy/',
            'website'      => 'https://stampede.ai',
            'supportEmail' => 'feedback@stampede.ai',
            'hasBrand'     => false
        ];
    }
}
