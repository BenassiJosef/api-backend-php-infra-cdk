<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 14/02/2017
 * Time: 19:59
 */

namespace App\Controllers\Integrations\Xero;

use App\Models\OauthUser;
use Doctrine\ORM\EntityManager;
use Spatie\ArrayToXml\ArrayToXml;

class _XeroInvoicesController extends _XeroController
{

    public function __construct(EntityManager $em, \XeroOAuth $xeroOAuth)
    {
        parent::__construct($em, $xeroOAuth);
    }

    public function newInvoice($invoice)
    {

        $customer = $this->em->createQueryBuilder()
            ->select('c')
            ->from(OauthUser::class, 'c')
            ->where('c.uid = :uid') // TODO OrgId replace
            ->setParameter('uid', $invoice['customer'])
            ->getQuery()
            ->getArrayResult();

        $invoiceArr = $this->invoiceArray($invoice, $customer[0]);

        $arr = ArrayToXml::convert($invoiceArr, 'Invoices', false);

        parent::xeroRequest('POST', 'Invoices', $arr);

        return $arr;
    }

    public function invoiceArray($invoice, $customer)
    {

        $cus      = new _XeroCustomerController($this->em, $this->xeroOAuth);
        $cusArray = $cus->customerArray($customer);
        $country  = strtoupper($customer['country']);

        $type = 'OUTPUT2';
        if ($country !== 'GB') {
            $type = 'NONE';
        }

        $status = 'DRAFT';
        if ($invoice['paid'] === true) {
            $status = 'SUBMITTED';
        }

        $lineItems = [];
        if (array_key_exists('items', $invoice)) {
            foreach ($invoice['items'] as $key => $item) {
                $lineItems[] = [
                    'Description'  => $item['name'],
                    'Quantity'     => $item['quantity'],
                    'UnitAmount'   => $item['amount'] / 100,
                    'ItemCode'     => $item['plan_id'],
                    'DiscountRate' => $invoice['discount'],
                    'TaxType'      => $type,
                    'AccountCode'  => 200,
                    'Tracking'     => [
                        'TrackingCategory' => [
                            [
                                'Name'   => 'Country',
                                'Option' => $country
                            ], [
                                'Name'   => 'Resellers',
                                'Option' => $customer['reseller']
                            ]
                        ]
                    ]
                ];
            }
        }

        if (is_null($invoice['tax']) || $invoice['tax'] === 0) {
            $tax = 0;
        } else {
            $tax = $invoice['tax'] / 100;
        }

        $invoiceArray = [
            'Invoice' => [
                'Type'            => 'ACCREC',
                'Reference'       => $invoice['invoice_id'],
                'Contact'         => $cusArray,
                'Date'            => date('c', $invoice['date']),
                'DueDate'         => date('c', $invoice['date']),
                'InvoiceNumber'   => $invoice['id_prefix'] . $invoice['id'],
                'CurrencyCode'    => $invoice['currency'],
                'Status'          => $status,
                'LineAmountTypes' => 'Exclusive',
                'SubTotal'        => $invoice['subtotal'] / 100,
                'TotalTax'        => $tax,
                'Total'           => $invoice['total'] / 100,
                'Url'             => 'https://stampede.ai',
                'LineItems'       => [
                    'LineItem' => $lineItems
                ]
            ]
        ];

        return $invoiceArray;
    }
}
