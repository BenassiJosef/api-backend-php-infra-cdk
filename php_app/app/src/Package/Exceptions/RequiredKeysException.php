<?php


namespace App\Package\Exceptions;


use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Throwable;

/**
 * Class RequiredKeysException
 * @package App\Package\Exceptions
 */
class RequiredKeysException extends BaseException
{
    /**
     * RequiredKeysException constructor.
     * @param string[] $requiredKeys
     * @param string[] $missingKeys
     * @throws Exception
     */
    public function __construct(array $requiredKeys, array $missingKeys)
    {
        $required = implode(', ', $requiredKeys);
        $missing  = implode(', ', $missingKeys);
        parent::__construct(
            "The key(s) (${missing}) are missing from the required keys (${required})",
            StatusCodes::HTTP_BAD_REQUEST,
            [
                'missing'  => $missingKeys,
                'required' => $requiredKeys,
            ]
        );
    }
}