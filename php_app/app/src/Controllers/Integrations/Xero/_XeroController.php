<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 14/02/2017
 * Time: 20:00
 */

namespace App\Controllers\Integrations\Xero;

use App\Controllers\Billing\Invoices\_InvoiceController;
use App\Models\OauthUser;
use Doctrine\ORM\EntityManager;
use Slim\Http\Response;
use Slim\Http\Request;
use Spatie\ArrayToXml\ArrayToXml;

class _XeroController
{
    protected $em;
    protected $xeroOAuth;


    public function __construct(EntityManager $em, \XeroOAuth $xeroOAuth)
    {
        $this->em     = $em;
        $initialCheck = $xeroOAuth->diagnostics();
        $checkErrors  = count($initialCheck);

        if ($checkErrors > 0) {
            throw new Exception('Could not finish constructing xero auth');
        }

        $xeroOAuth->config ['access_token']        = $xeroOAuth->config ['consumer_key'];
        $xeroOAuth->config ['access_token_secret'] = $xeroOAuth->config ['shared_secret'];

        $this->xeroOAuth = $xeroOAuth;
    }

    public function xeroRequest(string $method, string $type, $data)
    {
        $url = $this->xeroOAuth->url($type, 'core');
        $this->xeroOAuth->request($method, $url, [], $data);

        return $this->xeroOAuth->response;
    }


    public function testRoute(Request $request, Response $response)
    {
        //$req = $this->xeroOAuth->request('GET', $this->xeroOAuth->url('Accounts', 'core'));
        $invoiceController = new _InvoiceController($this->em);

        $invoice           = $invoiceController->getInvoice('in_19nQ0OLRYKuS0an1xGxU0QZM');
        $customer      = $this->em->createQueryBuilder()->select('c')
            ->from(OauthUser::class, 'c')
            ->where('c.uid = :customer') // TODO OrgId replace
            ->setParameter('customer', $invoice['message']['customer'])
            ->getQuery()
            ->getArrayResult();


        $cus      = new _XeroCustomerController($this->em, $this->xeroOAuth);
        $cusArray = $cus->customerArray($customer[0]);

        $in         = new _XeroInvoicesController($this->em, $this->xeroOAuth);
        $invoiceArr = $in->invoiceArray($invoice['message'], $cusArray);

        $arr = ArrayToXml::convert($invoiceArr, 'Invoices', false);

        $resp = $this->xeroRequest('POST', 'Invoices', $arr);

        $response->withStatus($resp['code'])->write(
            $resp['response']
        );

        return $response->withHeader('Content-type', 'text/xml');
    }

    public function findReseller(string $id)
    {
        $reseller = $this->em->createQueryBuilder()
            ->select('c.company')
            ->from(OauthUser::class, 'c')
            ->where('c.uid = :id') // TODO OrgId replace
            ->setParameter('id', $id)
            ->getQuery()
            ->getArrayResult();

        if (!empty($reseller)) {
            return $reseller[0]['company'];
        }

        return 'Not Set';
    }
}
