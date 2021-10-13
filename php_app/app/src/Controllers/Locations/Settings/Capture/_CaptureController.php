<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 02/05/2017
 * Time: 17:18
 */

namespace App\Controllers\Locations\Settings\Capture;

use App\Models\Locations\LocationSettings;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

class _CaptureController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getRoute(Request $request, Response $response)
    {
        $send = $this->get($request->getAttribute('serial'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateRoute(Request $request, Response $response)
    {
        $send = $this->update($request->getAttribute('serial'), $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function get(string $serial)
    {
        $getCapture = $this->em->createQueryBuilder()
            ->select('u.freeQuestions, u.customQuestions')
            ->from(LocationSettings::class, 'u')
            ->where('u.serial = :s')
            ->setParameter('s', $serial)
            ->getQuery()
            ->getArrayResult();

        if (empty($getCapture)) {
            return Http::status(200, []);
        }

        return Http::status(200, $getCapture[0]);
    }

    public function update(string $serial, array $update)
    {
        $allowedKeys = ['freeQuestions', 'customQuestions'];

        $get = $this->em->getRepository(LocationSettings::class)->findOneBy([
            'serial' => $serial
        ]);

        foreach ($update as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                $get->$key = $value;
            }
        }

        $this->em->flush();

        return Http::status(200, $get->getArrayCopy());

    }
}
