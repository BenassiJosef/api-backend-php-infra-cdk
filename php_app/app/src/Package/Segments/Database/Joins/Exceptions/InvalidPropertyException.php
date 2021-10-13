<?php


namespace App\Package\Segments\Database\Joins\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Class InvalidPropertyException
 * @package App\Package\Segments\Database\Joins\Exceptions
 */
class InvalidPropertyException extends BaseException
{
    /**
     * InvalidPropertyException constructor.
     * @param string $className
     * @param string $propertyName
     * @throws SegmentException
     */
    public function __construct(string $className, string $propertyName)
    {
        try {
            $reflectionClass = new ReflectionClass($className);
        } catch (ReflectionException $exception) {
            throw new SegmentException(
                "Invalid ClassName given",
                StatusCodes::HTTP_INTERNAL_SERVER_ERROR,
                $exception
            );
        }
        $validProperties = from($reflectionClass->getProperties())
            ->select(
                function (ReflectionProperty $property): string {
                    return $property->getName();
                }
            )
            ->toString(', ');
        parent::__construct(
            "(${propertyName}) is not a valid property of class "
            . "(${className}), only (${validProperties}) are valid properties",
            StatusCodes::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}