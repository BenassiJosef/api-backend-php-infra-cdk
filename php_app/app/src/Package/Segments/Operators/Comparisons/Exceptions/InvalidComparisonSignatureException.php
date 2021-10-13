<?php

namespace App\Package\Segments\Operators\Comparisons\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class InvalidComparisonSignatureException
 * @package App\Package\Segments\Operators\Comparisons\Exceptions
 */
class InvalidComparisonSignatureException extends BaseException
{
    public function __construct(array $signature, array $allowedSignatures)
    {
        $signatureString         = implode(', ', $signature);
        $allowedSignaturesString = from($allowedSignatures)
            ->select(
                function (array $signature): string {
                    return implode(', ', $signature);
                }
            )
            ->select(
                function (string $signatureString): string {
                    return "(${signatureString})";
                }
            )
            ->toString(', ');
        parent::__construct(
            "(${signatureString}) are not valid keys for a "
            ."comparison only (${allowedSignaturesString}) are valid signatures",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}