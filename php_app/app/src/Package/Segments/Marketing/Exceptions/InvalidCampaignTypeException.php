<?php

namespace App\Package\Segments\Marketing\Exceptions;

use Exception;
use Slim\Http\StatusCode;

/**
 * Class InvalidCampaignTypeException
 * @package App\Package\SegmentMarketing\Exceptions
 */
class InvalidCampaignTypeException extends MarketingException
{
    /**
     * InvalidCampaignTypeException constructor.
     * @param string $type
     * @param array|string[] $validTypes
     * @throws Exception
     */
    public function __construct(string $type, array $validTypes = ['email', 'sms'])
    {
        $validTypesString = implode(', ', $validTypes);
        parent::__construct(
            "The type (${type}) is not a valid campaign type only (${validTypesString}) are valid types",
            StatusCode::HTTP_BAD_REQUEST,
            [
                'type'       => $type,
                'validTypes' => $validTypes,
            ]
        );
    }
}