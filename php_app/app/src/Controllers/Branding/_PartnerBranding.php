<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 16/06/2017
 * Time: 13:59
 */

namespace App\Controllers\Branding;

use App\Models\PartnerBranding;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _PartnerBranding
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function savePartnerBrandingRoute(Request $request, Response $response)
    {
        $send = $this->savePartnerBranding($request->getAttribute('accessUser')['uid'], $request->getParsedBody());

        return $response->withJson($send, $send['status']);
    }

    public function savePartnerBranding(string $uid, array $body)
    {
        $brandingExists = $this->em->getRepository(PartnerBranding::class)->findOneBy([
            'admin' => $uid
        ]);

        if (is_object($brandingExists)) {
            $brandingExists->branding = $body['branding'];
        } else {
            $newPartner = new PartnerBranding($uid, $body['branding']);
            $this->em->persist($newPartner);
        }
        $this->em->flush();


        return Http::status(200, 'PARTNER_UPLOADED');
    }
}
