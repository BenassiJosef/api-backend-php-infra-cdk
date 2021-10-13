<?php


namespace App\Controllers\Billing\Subscriptions\Cancellation;

/**
 * Interface Email
 * @package App\Controllers\Billing\Subscriptions\Cancellation
 */
interface Email
{
    /**
     * @return array
     */
    public function getSendTo(): array ;

    /**
     * @return array
     */
    public function getArguments(): array ;

    /**
     * @return string
     */
    public function getTemplate(): string ;

    /**
     * @return string
     */
    public function getSubject(): string ;
}