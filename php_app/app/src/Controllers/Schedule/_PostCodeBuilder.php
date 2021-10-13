<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 18/05/2017
 * Time: 17:32
 */

namespace App\Controllers\Schedule;

use App\Models\UserProfile;
use App\Utils\Http;
use Curl\Curl;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Slim\Http\Response;
use Slim\Http\Request;

class _PostCodeBuilder
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getPostCodeRoute(Request $request, Response $response)
    {
        $offset = 0;
        $params = $request->getQueryParams();
        if (isset($params['offset'])) {
            $offset = $params['offset'];
        }

        $send = $this->getPostCode($offset);

        $this->em->clear();

        return $response->withJson($send, 200);
    }

    private function getPostCode($offset)
    {
        $sql = $this->em->createQueryBuilder()
            ->select('u.postcode, u.id')
            ->from(UserProfile::class, 'u')
            ->where('u.postcode IS NOT NULL')
            ->andWhere('u.postcodeValid IS NULL')
            ->andWhere('u.lat IS NULL')
            ->andWhere('u.postcode != :empty')
            ->setParameter('empty', '')
            ->setFirstResult($offset)
            ->setMaxResults(100)
            ->orderBy('u.id');

        $results = new Paginator($sql);
        $results->setUseOutputWalkers(false);

        $sql = $results->getIterator()->getArrayCopy();

        if (empty($sql)) {
            return Http::status(200);
        }

        $return = [
            'has_more'    => false,
            'total'       => count($sql),
            'next_offset' => $offset + 100
        ];

        if ($offset <= $return['total'] && count($sql) !== $return['total']) {
            $return['has_more'] = true;
        }

        $postcodes            = [];
        $associatedProfileIds = [];

        foreach ($sql as $key => $value) {
            $upperCode = strtoupper(str_replace(' ', '', $value['postcode']));

            if ($this->IsPostcode($upperCode)) {
                $postcodes[]                      = $upperCode;
                $associatedProfileIds[$upperCode] = $value['id'];
            } else {
                $fakePostcode           = $this->em->getRepository(UserProfile::class)->findOneBy([
                    'id'       => $value['id'],
                    'postcode' => $value['postcode']
                ]);
                $fakePostcode->postcode = null;
            }
        }
        $this->em->flush();

        $request = new Curl();

        $toBeSent = json_encode(['postcodes' => $postcodes]);
        $request->setHeader('Content-Type', 'application/json');
        $request->setHeader('Content-Length', strlen($toBeSent));
        $request->post('http://api.postcodes.io/postcodes', $toBeSent);

        if ($request->error) {
            return Http::status(400, $request->errorMessage);
        }

        $mapdataArray = $request->response->result;
        foreach ($mapdataArray as $key => $value) {
            if (is_object($value)) {
                if (is_null($value->result)) {
                    $updateUser           = $this->em->getRepository(UserProfile::class)->findOneBy([
                        'id' => $associatedProfileIds[$value->query]
                    ]);
                    $updateUser->postcode = null;
                    continue;
                }
                $castValue  = get_object_vars($value->result);
                $updateUser = $this->em->getRepository(UserProfile::class)->findOneBy([
                    'id' => $associatedProfileIds[str_replace(' ', '', $castValue['postcode'])]
                ]);
                if (is_object($updateUser)) {
                    $updateUser->postcodeValid = true;
                    $updateUser->lat           = $castValue['latitude'];
                    $updateUser->lng           = $castValue['longitude'];
                }
            }
        }

        $this->em->flush();

        return Http::status(200, $return);
    }

    public function IsPostcode($postcode)
    {
        $postcode = strtoupper(str_replace(' ', '', $postcode));
        if (preg_match("/^[A-Z]{1,2}[0-9]{2,3}[A-Z]{2}$/", $postcode)
            || preg_match("/^[A-Z]{1,2}[0-9]{1}[A-Z]{1}[0-9]{1}[A-Z]{2}$/", $postcode)
            || preg_match("/^GIR0[A-Z]{2}$/", $postcode)
        ) {
            return true;
        } else {
            return false;
        }
    }
}
