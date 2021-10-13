<?php
/**
 * Created by PhpStorm.
 * User: patrickclover
 * Date: 14/02/2017
 * Time: 22:02
 */

namespace App\Controllers\Integrations\Xero;

use Doctrine\ORM\EntityManager;

class _XeroCustomerController extends _XeroController
{

    public function __construct(EntityManager $em, \XeroOAuth $xeroOAuth)
    {
        parent::__construct($em, $xeroOAuth);
    }

    public function customerArray(array $customer = [])
    {
        return [
            'Name'          => $customer['first'] . ' ' . $customer['last'] . '(' . $customer['company'] .')',
            'ContactStatus' => 'ACTIVE',
            'EmailAddress'  => $customer['email'],
            'FirstName'     => $customer['first'],
            'LastName'      => $customer['last'],
        ];
    }
}
