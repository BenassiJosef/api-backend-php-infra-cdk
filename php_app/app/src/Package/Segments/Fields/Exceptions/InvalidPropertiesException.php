<?php


namespace App\Package\Segments\Fields\Exceptions;

use App\Package\Exceptions\BaseException;
use App\Package\Segments\Exceptions\SegmentException;
use OAuth2\HttpFoundationBridge\Response as StatusCodes;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

class InvalidPropertiesException extends BaseException
{
    public function __construct(string $className, string ...$propertyName)
    {
        $propertyNamesString = implode(', ', $propertyName);
        try {
            $reflectionClass = new ReflectionClass($className);
        } catch (ReflectionException $exception) {
            throw new SegmentException(
                "(${className}) is not a valid class",
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
            "(${propertyNamesString}) are not valid properties"
            . " of class (${className}), only (${validProperties}) are valid properties",
        );
    }
}