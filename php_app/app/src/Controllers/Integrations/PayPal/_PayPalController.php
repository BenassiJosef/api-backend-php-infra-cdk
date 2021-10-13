<?php

namespace App\Controllers\Integrations\PayPal;

use App\Models\Integrations\PayPal\PayPalAccount;
use App\Models\Integrations\PayPal\PayPalAccountAccess;
use App\Models\Locations\LocationSettings;
use App\Models\Organization;
use App\Package\Organisations\OrganisationIdProvider;
use App\Package\Organisations\OrganizationProvider;
use App\Package\Organisations\OrganizationService;
use App\Utils\CacheEngine;
use App\Utils\Http;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;

/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 04/01/2017
 * Time: 10:48
 */
class _PayPalController
{

    protected $em;
    protected $nearlyCache;

    /**
     * @var OrganizationProvider
     */
    private $organisationProvider;

    public function __construct(EntityManager $em)
    {
        $this->em                   = $em;
        $this->nearlyCache          = new CacheEngine(getenv('NEARLY_REDIS'));
        $this->organisationProvider = new OrganizationProvider($this->em);
    }

    public function createAccountRoute(Request $request, Response $response)
    {

        $organization = $this->organisationProvider->organizationForRequest($request);
        $send         = $this->createAccount($organization, $request->getParsedBody());

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function updateAccountRoute(Request $request, Response $response)
    {

        $send = $this->updateAccount($request->getAttribute('orgId'), $request->getParsedBody(),
            $request->getAttribute('id'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function retrieveAccountsRoute(Request $request, Response $response)
    {
        $send = $this->retrieveAccounts($request->getAttribute('orgId'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function retrieveAccountRoute(Request $request, Response $response)
    {
        $send = $this->retrieveAccount($request->getAttribute('id'), '');

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function deleteAccountRoute(Request $request, Response $response)
    {
        $send = $this->deleteAccount($request->getAttribute('orgId'), $request->getAttribute('id'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function getSerialRoute(Request $request, Response $response)
    {
        $send = $this->getFromSerial($request->getAttribute('serial'));

        if (is_object($send)) {
            $send = Http::status(200, $send->getArrayCopy());
        }

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function linkAccountWithSerialRoute(Request $request, Response $response)
    {
        $send = $this->linkAccountWithSerial($request->getAttribute('serial'), $request->getAttribute('id'));

        $this->em->clear();

        return $response->withJson($send, $send['status']);
    }

    public function createAccount(Organization $organization, array $body)
    {
        $existsAlready = $this->em->getRepository(PayPalAccount::class)->findOneBy([
            'username'  => $body['username'],
            'password'  => $body['password'],
            'signature' => $body['signature']
        ]);

        if (is_object($existsAlready)) {
            return Http::status(409, 'ACCOUNT_EXISTS_ALREADY');
        }

        $newPayPalAccount = new PayPalAccount($body['name'], $body['username'], $body['password'], $body['signature']);
        $this->em->persist($newPayPalAccount);

        $newPayPalAccountAccess = new PayPalAccountAccess($organization, $newPayPalAccount->id);
        $this->em->persist($newPayPalAccountAccess);

        $this->em->flush();

        return Http::status(200, $newPayPalAccount->getArrayCopy());
    }

    public function updateAccount(string $orgId, array $body, string $accountId)
    {


        $hasAccess = $this->em->createQueryBuilder()
            ->select('u.paypalAccount')
            ->from(PayPalAccountAccess::class, 'u')
            ->where('s.organizationId = :orgId')
            ->andWhere('u.paypalAccount = :account')
            ->setParameter('orgId', $orgId)
            ->setParameter('account', $accountId)
            ->getQuery()
            ->getArrayResult();

        if (empty($hasAccess)) {
            return Http::status(403, 'USER_DOES_NOT_HAVE_ACCESS');
        }

        $account = $this->em->getRepository(PayPalAccount::class)->findOneBy([
            'id' => $accountId
        ]);

        if (is_null($account)) {
            return Http::status(404, ' COULD_NOT_LOCATE_ACCOUNT');
        }

        $account->username  = $body['username'];
        $account->password  = $body['password'];
        $account->signature = $body['signature'];
        $account->name      = $body['name'];

        $this->em->flush();

        $networksWithAccountInUse = $this->em->getRepository(LocationSettings::class)->findBy([
            'paypalAccount' => $accountId
        ]);

        foreach ($networksWithAccountInUse as $network) {
            $this->nearlyCache->delete($network->serial . ':paypal');
        }

        return Http::status(200, $account->getArrayCopy());
    }

    public function retrieveAccounts(string $orgId)
    {

        $accounts = $this->em->createQueryBuilder()
            ->select('u.id, u.name')
            ->from(PayPalAccount::class, 'u')
            ->join(PayPalAccountAccess::class, 's', 'WITH', 'u.id = s.paypalAccount')
            ->where('s.organizationId = :orgId')// TODO OrgId replace
            ->setParameter('orgId', $orgId)
            ->getQuery()
            ->getArrayResult();

        if (empty($accounts)) {
            return Http::status(204);
        }

        return Http::status(200, $accounts);
    }

    public function retrieveAccount(string $accountId, string $serial)
    {
        if (!empty($serial)) {
            $exists = $this->nearlyCache->fetch($serial . ':paypal');
            if (!is_bool($exists)) {
                return Http::status(200, $exists);
            }
        }

        $getAccount = $this->em->getRepository(PayPalAccount::class)->findOneBy([
            'id' => $accountId
        ]);

        if (is_null($getAccount)) {
            return Http::status(404, 'COULD_NOT_LOCATE_ACCOUNT');
        }

        if (!empty($serial)) {
            $this->nearlyCache->save($serial . ':paypal', $getAccount->getArrayCopy());
        }

        return Http::status(200, $getAccount->getArrayCopy());
    }

    public function deleteAccount(string $orgId, string $accountId)
    {

        $canDelete = $this->em->createQueryBuilder()
            ->select('u.id')
            ->from(PayPalAccount::class, 'u')
            ->join(PayPalAccountAccess::class, 's', 'WITH', 'u.id = s.paypalAccount')
            ->where('s.organizationId = :orgId')
            ->andWhere('s.paypalAccount = :pid')
            ->setParameter('orgId', $orgId)
            ->setParameter('pid', $accountId)
            ->getQuery()
            ->getArrayResult();

        if (empty($canDelete)) {
            return Http::status(409, 'INVALID_COMBINATION');
        }

        $networksWithAccountInUse = $this->em->getRepository(LocationSettings::class)->findBy([
            'paypalAccount' => $accountId
        ]);

        foreach ($networksWithAccountInUse as $network) {
            $network->paypalAccount = '';
            $this->nearlyCache->delete($network->serial . ':paypal');
        }


        $getAccountAccess = $this->em->getRepository(PayPalAccountAccess::class)->findBy([
            'paypalAccount' => $accountId
        ]);

        foreach ($getAccountAccess as $access) {
            $this->em->remove($access);
        }

        $getAccount = $this->em->getRepository(PayPalAccount::class)->findOneBy([
            'id' => $accountId
        ]);

        $this->em->remove($getAccount);

        $this->em->flush();

        return Http::status(200, ['accountId' => $accountId]);
    }


    public function linkAccountWithSerial(string $serial, string $accountId)
    {
        $network = $this->getFromSerial($serial);

        if (is_array($network)) {
            return $network;
        }

        $network->paypalAccount = $accountId;

        $this->em->flush();

        return Http::status(200, $network->getArrayCopy());
    }

    /**
     * @param $serial
     * @return array|null|object
     */

    public function getFromSerial(string $serial)
    {
        $network = $this->em->getRepository(LocationSettings::class)->findOneBy([
            'serial' => $serial
        ]);

        if (is_null($network)) {
            return Http::status(404, 'COULD_NOT_LOCATE_LOCATION');
        }

        return $network;
    }
}
