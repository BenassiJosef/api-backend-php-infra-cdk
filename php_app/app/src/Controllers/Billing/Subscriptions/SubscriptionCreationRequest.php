<?php


namespace App\Controllers\Billing\Subscriptions;

use App\Utils\Http;

class SubscriptionCreationRequest
{
    /**
     * @var bool $trial
     */
    private $trial;

    /**
     * @var string $planId
     */
    private $planId;

    /**
     * @var bool $hosted
     */
    private $hosted;

    /**
     * @var string $methodName
     */
    private $methodName;

    /**
     * @var ?string $methodSerial
     */
    private $methodSerial;

    /**
     * SubscriptionCreationRequest constructor.
     * @param bool $trial
     * @param string $planId
     * @param bool $hosted
     * @param string $methodName
     */
    public function __construct(
        bool $trial,
        string $planId,
        bool $hosted,
        string $methodName,
        string $methodSerial = null
    )
    {
        $this->trial      = $trial;
        $this->planId     = $planId;
        $this->hosted     = $hosted;
        $this->methodName = $methodName;
        $this->methodSerial = $methodSerial;
    }

    /**
     * @param array $input
     * @return SubscriptionCreationRequest|array
     */
    public static function createFromArray(array $input)
    {
        $expectedFields = [
            'trial',
            'planId',
            'hosted',
            'method'
        ];
        foreach ($expectedFields as $field) {
            if (!array_key_exists($field, $input)){
                return Http::status(400, strtoupper($field)."_MISSING");
            }
        }
        $method = $input['method'];
        if (!array_key_exists('name', $method)){
           return Http::status(400, "METHOD_NAME_MISSING");
        }

        $methodSerial = null;
        if (array_key_exists('serial', $input)){
            $methodSerial = $input['serial'];
        }
        try {
            return new self(
                $input['trial'],
                $input['planId'],
                $input['hosted'],
                $method['name'],
                $methodSerial
            );
        } catch (\Throwable $exception){
            return Http::status(400, 'FIELDS_WRONG_TYPE');
        }
    }

    /**
     * @return bool
     */
    public function isTrial(): bool
    {
        return $this->trial;
    }

    /**
     * @return string
     */
    public function getPlanId(): string
    {
        return $this->planId;
    }

    /**
     * @return bool
     */
    public function isHosted(): bool
    {
        return $this->hosted;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @return string|null
     */
    public function getMethodSerial()
    {
        return $this->methodSerial;
    }


}