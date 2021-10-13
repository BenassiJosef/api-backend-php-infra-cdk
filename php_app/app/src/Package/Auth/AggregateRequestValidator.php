<?php


namespace App\Package\Auth;


use Slim\Http\Request;

/**
 * Class AggregateRequestValidator
 * @package App\Package\Auth
 */
class AggregateRequestValidator implements RequestValidator
{
    /**
     * @param RequestValidator ...$requestValidators
     * @return static
     */
    public static function fromRequestValidators(RequestValidator ...$requestValidators): self
    {
        return new self(...$requestValidators);
    }

    /**
     * @var RequestValidator[] $requestValidators
     */
    private $requestValidators;

    /**
     * AggregateRequestValidator constructor.
     * @param RequestValidator[] $requestValidators
     */
    public function __construct(RequestValidator ...$requestValidators)
    {
        $this->requestValidators = $requestValidators;
    }

    /**
     * @inheritDoc
     */
    public function canRequest(string $service, Request $request): bool
    {
        if (count($this->requestValidators) === 0) {
            return false;
        }
        foreach ($this->requestValidators as $requestValidator) {
            if (!$requestValidator->canRequest($service, $request)) {
                return false;
            }
        }
        return true;
    }

    public function addRequestValidator(RequestValidator $requestValidator): self
    {
        $this->requestValidators[] = $requestValidator;
        return $this;
    }
}