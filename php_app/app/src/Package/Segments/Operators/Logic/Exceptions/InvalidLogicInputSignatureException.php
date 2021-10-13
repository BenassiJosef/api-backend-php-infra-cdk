<?php


namespace App\Package\Segments\Operators\Logic\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;

/**
 * Class InvalidLogicInputSignatureException
 * @package App\Package\Segments\Operators\Logic\Exceptions
 */
class InvalidLogicInputSignatureException extends BaseException
{
    /**
     * InvalidLogicInputSignatureException constructor.
     * @param array $signature
     * @param array $allowedSignature
     */
    public function __construct(array $signature, array $allowedSignature)
    {
        $signatureString        = implode(', ', $signature);
        $allowedSignatureString = implode(', ', $allowedSignature);
        parent::__construct(
            "(${signatureString}) is not a valid signature for Logic, only (${allowedSignatureString}) is.",
            StatusCodes::HTTP_BAD_REQUEST
        );
    }
}