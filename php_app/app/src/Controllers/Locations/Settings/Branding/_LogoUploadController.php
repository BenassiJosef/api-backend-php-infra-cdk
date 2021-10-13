<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 27/03/2017
 * Time: 12:42
 */

namespace App\Controllers\Locations\Settings\Branding;

use App\Controllers\Integrations\Uploads\_UploadStorageController;
use App\Models\Locations\Branding\LocationBranding;
use App\Models\Locations\LocationSettings;
use App\Utils\Http;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _LogoUploadController
{
    private $uploads;
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->uploads = new _UploadStorageController($em);
        $this->em      = $em;
    }

    public function postRoute(Request $request, Response $response)
    {
        $serial = $request->getAttribute('serial');
        $body   = $request->getParsedBody();
        $send   = $this->setImage($serial, $body);

        $this->em->clear();

        return $response->withStatus($send['status'])->withJson($send);
    }

    public function deleteRoute(Request $request, Response $response)
    {
        $serial = $request->getAttribute('serial');
        $body   = $request->getParsedBody();
        $send   = $this->deleteImage($serial, $body['file'], $body['type'], $body['pastOrCurrent']);

        $this->em->clear();

        return $response->withStatus($send['status'])->withJson($send);
    }

    public function setImage(string $serial, $file)
    {
        if (empty($file)) {
            return Http::status(400, 'NO_FILES_ATTACHED');
        }

        $network = $this->em->getRepository(LocationSettings::class)->findOneBy([
            'serial' => $serial
        ]);

        if (is_null($network)) {
            return Http::status(404, 'NO_SUCH_LOCATION');
        }

        $networkBranding = $this->em->getRepository(LocationBranding::class)->findOneBy([
            'id' => $network->branding
        ]);

        if (is_null($network)) {
            return Http::status(404, 'FAILED_TO_LOCATED_NETWORK_BRANDING');
        }

        $allowedTypes = ['header', 'background'];

        if (!array_key_exists('type', $file)) {
            return Http::status(400, 'REQUIRES_TYPE');
        }

        if (!in_array($file['type'], $allowedTypes)) {
            return Http::status(409, 'INVALID_TYPE');
        }

        if ($file['type'] === 'header') {
            $type                         = 'headerImage';
            $networkBranding->headerImage = $file['url'];
        } else {
            $type                             = 'backgroundImage';
            $networkBranding->backgroundImage = $file['url'];
        }

        $this->em->flush();

        $curl = new Curl();
        $curl->delete('http://nearly.online/cache/' . $serial);

        return Http::status(200, $networkBranding->getArrayCopy());
    }

    private function deleteImage(string $serial, $file, string $type, string $pastOrCurrent)
    {
        $network = $this->em->getRepository(LocationSettings::class)->findOneBy([
            'serial' => $serial
        ]);

        if (is_null($network)) {
            return Http::status(404, 'FAILED_TO_LOCATED_NETWORK');
        }

        return Http::status(200, $network->getArrayCopy());
    }
}
