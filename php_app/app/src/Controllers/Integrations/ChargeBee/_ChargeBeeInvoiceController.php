<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 06/07/2017
 * Time: 09:06
 */

namespace App\Controllers\Integrations\ChargeBee;

use App\Models\Integrations\ChargeBee\Invoice;
use App\Models\Integrations\ChargeBee\InvoiceDiscount;
use App\Models\Integrations\ChargeBee\InvoiceLineItem;
use App\Models\Integrations\ChargeBee\InvoiceLineItemDiscounts;
use App\Models\Integrations\ChargeBee\InvoiceLineItemTax;
use Doctrine\ORM\EntityManager;

class _ChargeBeeInvoiceController
{
    private $errorHandler;
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->errorHandler = new _ChargeBeeHandleErrors();
        $this->em           = $em;
    }

    public function collectPayment(array $body)
    {
        $collectPayment = function ($body) {
            return \ChargeBee_Invoice::collectPayment($body['invoiceId'])->invoice()->getValues();
        };

        return $this->errorHandler->handleErrors($collectPayment, $body);
    }

    public function voidInvoice(array $body)
    {
        $voidInvoice = function ($body) {
            return \ChargeBee_Invoice::voidInvoice($body['invoiceId'], [
                'comment' => $body['comment']
            ])->invoice()->getValues();
        };

        return $this->errorHandler->handleErrors($voidInvoice, $body);
    }

    public function writeOff(array $body)
    {
        $writeOff = function ($body) {
            return \ChargeBee_Invoice::writeOff($body['invoiceId'], [
                'comment' => $body['comment']
            ]);
        };

        return $this->errorHandler->handleErrors($writeOff, $body);
    }

    public function refund(array $body)
    {
        $refund = function ($body) {
            return \ChargeBee_Invoice::refund($body['invoiceId'], [
                'comment' => $body['comment']
            ]);
        };

        return $this->errorHandler->handleErrors($refund, $body);
    }

    public function createFromWebHook(array $invoiceEvent)
    {
        $newInvoice = new Invoice();

        foreach ($invoiceEvent as $invoiceKey => $invoiceValue) {
            if ($invoiceKey === 'line_items') {
                foreach ($invoiceValue as $lineItem) {
                    $newInvoiceLineItem             = new InvoiceLineItem();
                    $newInvoiceLineItem->invoice_id = $invoiceEvent['id'];
                    foreach ($lineItem as $lineItemKey => $lineItemValue) {
                        $newInvoiceLineItem->$lineItemKey = $lineItemValue;
                    }
                    $this->em->persist($newInvoiceLineItem);
                }
            } elseif ($invoiceKey === 'discounts') {
                foreach ($invoiceValue as $discount) {
                    $newDiscount             = new InvoiceDiscount();
                    $newDiscount->invoice_id = $invoiceEvent['id'];
                    foreach ($discount as $discountKey => $discountValue) {
                        $newDiscount->$discountKey = $discountValue;
                    }
                    $this->em->persist($newDiscount);
                }
            } elseif ($invoiceKey === 'line_item_discounts') {
                foreach ($invoiceValue as $discount) {
                    $newLineItemDiscount             = new InvoiceLineItemDiscounts();
                    $newLineItemDiscount->invoice_id = $invoiceEvent['id'];
                    foreach ($discount as $discountKey => $discountValue) {
                        $newLineItemDiscount->$discountKey = $discountValue;
                    }
                    $this->em->persist($newLineItemDiscount);
                }
            } elseif ($invoiceKey === 'line_item_taxes') {
                foreach ($invoiceValue as $tax) {
                    $newInvoiceLineItemTax             = new InvoiceLineItemTax();
                    $newInvoiceLineItemTax->invoice_id = $invoiceEvent['id'];
                    foreach ($tax as $taxKey => $taxValue) {
                        $newInvoiceLineItemTax->$taxKey = $taxValue;
                    }
                    $this->em->persist($newInvoiceLineItemTax);
                }
            } elseif (!is_array($invoiceValue)) {
                if ($invoiceKey === 'subscription_id') {
                    continue;
                } elseif ($invoiceKey === 'id') {
                    $newInvoice->invoice_id = $invoiceEvent['id'];
                } else {
                    $newInvoice->$invoiceKey = $invoiceValue;
                }
            }
        }

        $this->em->persist($newInvoice);
        $this->em->flush();

        $this->em->clear();
    }

    public function updateFromWebHook(array $invoiceEvent)
    {

        $this->em->createQueryBuilder()
            ->delete(Invoice::class, 'i')
            ->where('i.invoice_id = :id')
            ->setParameter('id', $invoiceEvent['id'])
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->delete(InvoiceLineItem::class, 'i')
            ->where('i.invoice_id = :id')
            ->setParameter('id', $invoiceEvent['id'])
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->delete(InvoiceDiscount::class, 'i')
            ->where('i.invoice_id = :id')
            ->setParameter('id', $invoiceEvent['id'])
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->delete(InvoiceLineItemDiscounts::class, 'i')
            ->where('i.invoice_id = :id')
            ->setParameter('id', $invoiceEvent['id'])
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->delete(InvoiceLineItemTax::class, 'i')
            ->where('i.invoice_id = :id')
            ->setParameter('id', $invoiceEvent['id'])
            ->getQuery()
            ->execute();

        return $this->createFromWebHook($invoiceEvent);
    }
}
