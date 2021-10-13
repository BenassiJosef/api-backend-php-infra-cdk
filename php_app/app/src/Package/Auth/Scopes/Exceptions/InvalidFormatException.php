<?php


namespace App\Package\Auth\Scopes\Exceptions;


use App\Package\Exceptions\BaseException;
use Exception;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use Slim\Http\StatusCode;
use Throwable;

/**
 * Class InvalidScopeFormatException
 * @package App\Package\Auth\Exceptions
 */
class InvalidFormatException extends ScopeException
{
    /**
     * @var string[] $allowedFormats
     */
    private static $allowedFormats = [
        '{scope type}',
        '{scope type}:{service}'
    ];

    /**
     * InvalidScopeFormatException constructor.
     * @param string $scope
     * @throws Exception
     */
    public function __construct(string $scope)
    {
        $allowedFormats = implode(', ', self::$allowedFormats);
        parent::__construct(
            "Scope (${scope}) is not in a valid format, (${allowedFormats}) are the only allowed formats.",
            StatusCode::HTTP_BAD_REQUEST,
            [
                'allowedFormats' => $allowedFormats,
            ]
        );
    }
}