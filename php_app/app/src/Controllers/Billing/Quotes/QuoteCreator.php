<?php


namespace App\Controllers\Billing\Quotes;

use App\Models\Organization;
use Slim\Http\Request;

/**
 * Interface QuoteCreator
 * @package App\Controllers\Billing\Quotes
 */
interface QuoteCreator
{
    /**
     * @param array $body
     * @param string $uid
     * @return mixed
     */
    public function createQuote(Request $body, Organization $resellerOrganisation, Organization $customerOrganisation);
}
