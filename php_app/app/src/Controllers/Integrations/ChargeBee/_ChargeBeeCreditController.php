<?php
/**
 * Created by PhpStorm.
 * User: jamieaitken
 * Date: 22/09/2017
 * Time: 09:41
 */

namespace App\Controllers\Integrations\ChargeBee;

use App\Models\Integrations\ChargeBee\CreditNote;
use App\Models\Integrations\ChargeBee\CreditNoteDiscount;
use App\Models\Integrations\ChargeBee\CreditNoteLineItem;
use App\Models\Integrations\ChargeBee\CreditNoteLineItemDiscounts;
use App\Models\Integrations\ChargeBee\CreditNoteLineItemTax;
use App\Models\Integrations\ChargeBee\CreditNoteTaxes;
use Doctrine\ORM\EntityManager;

class _ChargeBeeCreditController
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function createOrUpdate(array $event)
    {
        $creditNoteExists = $this->em->getRepository(CreditNote::class)->findOneBy([
            'id' => $event['id']
        ]);

        if (is_object($creditNoteExists)) {
            $creditNoteTax = $this->em->getRepository(CreditNoteTaxes::class)->findBy([
                'credit_note_id' => $event['id']
            ]);

            $creditNoteLineItemTax = $this->em->getRepository(CreditNoteLineItemTax::class)->findBy([
                'credit_note_id' => $event['id']
            ]);

            $creditNoteLineItemDiscount = $this->em->getRepository(CreditNoteLineItemDiscounts::class)->findBy([
                'credit_note_id' => $event['id']
            ]);

            $creditNoteLineItem = $this->em->getRepository(CreditNoteLineItem::class)->findBy([
                'credit_note_id' => $event['id']
            ]);

            $creditNoteDiscount = $this->em->getRepository(CreditNoteDiscount::class)->findBy([
                'credit_note_id' => $event['id']
            ]);

            foreach ($creditNoteTax as $tax) {
                $this->em->remove($tax);
            }

            foreach ($creditNoteLineItemTax as $lineItemTax) {
                $this->em->remove($lineItemTax);
            }

            foreach ($creditNoteLineItemDiscount as $lineItemDiscount) {
                $this->em->remove($lineItemDiscount);
            }

            foreach ($creditNoteLineItem as $lineItem) {
                $this->em->remove($lineItem);
            }

            foreach ($creditNoteDiscount as $discount) {
                $this->em->remove($discount);
            }

            $this->em->remove($creditNoteExists);

            $this->em->flush();
        }

        $newCreditNote = new CreditNote();
        foreach ($event as $creditKey => $creditValue) {
            if ($creditKey === 'line_items') {
                foreach ($creditValue as $lineItem) {
                    $newCreditLineItem = new CreditNoteLineItem();
                    $newCreditLineItem->credit_note_id = $event['id'];
                    foreach ($lineItem as $lineItemKey => $lineItemValue) {
                        $newCreditLineItem->$lineItemKey = $lineItemValue;
                    }
                    $this->em->persist($newCreditLineItem);
                }
            } elseif ($creditKey === 'taxes') {
                foreach ($creditValue as $tax) {
                    $newCreditTax = new CreditNoteTaxes();
                    $newCreditTax->credit_note_id = $event['id'];
                    foreach ($tax as $taxKey => $taxValue) {
                        $newCreditTax->$taxKey = $taxValue;
                    }
                    $this->em->persist($newCreditTax);
                }
            } elseif ($creditKey === 'line_item_taxes') {
                foreach ($creditValue as $lineItemTax) {
                    $newCreditLineTax = new CreditNoteLineItemTax();
                    $newCreditLineTax->credit_note_id = $event['id'];
                    foreach ($lineItemTax as $lineItemTaxKey => $lineItemTaxValue) {
                        $newCreditLineTax->$lineItemTaxKey = $lineItemTaxValue;
                    }
                    $this->em->persist($newCreditLineTax);
                }
            } elseif ($creditKey === 'line_item_discounts') {
                foreach ($creditValue as $lineItemDiscount) {
                    $newCreditLineItemDiscount = new CreditNoteLineItemDiscounts();
                    $newCreditLineItemDiscount->credit_note_id = $event['id'];
                    foreach ($lineItemDiscount as $lineItemDiscountKey => $lineItemDiscountValue) {
                        $newCreditLineItemDiscount->$lineItemDiscountKey = $lineItemDiscountValue;
                    }
                    $this->em->persist($newCreditLineItemDiscount);
                }
            } elseif ($creditKey === 'discounts') {
                foreach ($creditValue as $creditNoteDiscount) {
                    $newCreditDiscount = new CreditNoteDiscount();
                    $newCreditDiscount->credit_note_id = $event['id'];
                    foreach ($creditNoteDiscount as $creditNoteDiscountKey => $creditNoteDiscountValue) {
                        $newCreditDiscount->$creditNoteDiscountKey = $creditNoteDiscountValue;
                    }
                    $this->em->persist($newCreditDiscount);
                }
            } elseif (!is_array($creditValue)) {
                if ($creditKey === 'subscription_id') {
                    continue;
                } else {
                    $newCreditNote->$creditKey = $creditValue;
                }
            }
        }
        $this->em->persist($newCreditNote);
        $this->em->flush();
    }

    public function delete(array $event)
    {
        $creditNoteExists = $this->em->getRepository(CreditNote::class)->findOneBy([
            'id' => $event['id']
        ]);

        if (is_object($creditNoteExists)) {
            $creditNoteTax = $this->em->getRepository(CreditNoteTaxes::class)->findBy([
                'credit_note_id' => $event['id']
            ]);

            $creditNoteLineItemTax = $this->em->getRepository(CreditNoteLineItemTax::class)->findBy([
                'credit_note_id' => $event['id']
            ]);

            $creditNoteLineItemDiscount = $this->em->getRepository(CreditNoteLineItemDiscounts::class)->findBy([
                'credit_note_id' => $event['id']
            ]);

            $creditNoteLineItem = $this->em->getRepository(CreditNoteLineItem::class)->findBy([
                'credit_note_id' => $event['id']
            ]);

            $creditNoteDiscount = $this->em->getRepository(CreditNoteDiscount::class)->findBy([
                'credit_note_id' => $event['id']
            ]);

            foreach ($creditNoteTax as $tax) {
                $this->em->remove($tax);
            }

            foreach ($creditNoteLineItemTax as $lineItemTax) {
                $this->em->remove($lineItemTax);
            }

            foreach ($creditNoteLineItemDiscount as $lineItemDiscount) {
                $this->em->remove($lineItemDiscount);
            }

            foreach ($creditNoteLineItem as $lineItem) {
                $this->em->remove($lineItem);
            }

            foreach ($creditNoteDiscount as $discount) {
                $this->em->remove($discount);
            }

            $this->em->remove($creditNoteExists);

            $this->em->flush();
        }
    }
}
