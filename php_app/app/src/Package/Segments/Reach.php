<?php

namespace App\Package\Segments;

use App\Package\Segments\Database\Parse\Context;
use App\Package\Segments\Exceptions\InvalidReachInputException;

/**
 * Class Reach
 * @package App\Package\Segments
 */
class Reach implements \JsonSerializable
{
    /**
     * @var bool[] $requiredReachProperties
     */
    private static $requiredReachProperties = [
        Context::MODE_EMAIL => true,
        Context::MODE_SMS   => true,
        Context::MODE_ALL   => true,
    ];

    /**
     * @param array $data
     * @throws InvalidReachInputException
     */
    public static function validateReachInput(array $data): void
    {
        if (!array_key_exists('reach', $data)) {
            throw new InvalidReachInputException($data);
        }
        $reachInput = $data['reach'];
        foreach (array_keys(self::$requiredReachProperties) as $property) {
            if (!array_key_exists($property, $reachInput)) {
                throw new InvalidReachInputException($data);
            }
        }
    }

    /**
     * @param array $data
     * @return static
     * @throws InvalidReachInputException
     */
    public static function fromArray(array $data): self
    {
        self::validateReachInput($data);
        $reachInput = $data['reach'];
        return new self(
            $reachInput[Context::MODE_ALL],
            $reachInput[Context::MODE_SMS],
            $reachInput[Context::MODE_EMAIL]
        );
    }

    const VERSION = 1;

    /**
     * @var int[] $counts
     */
    private $counts;

    /**
     * Reach constructor.
     * @param int $all
     * @param int $sms
     * @param int $email
     */
    public function __construct(
        int $all = 0,
        int $sms = 0,
        int $email = 0
    ) {
        $this->counts = [
            Context::MODE_ALL   => $all,
            Context::MODE_SMS   => $sms,
            Context::MODE_EMAIL => $email,
        ];
    }

    /**
     * @param string $mode
     * @return int
     */
    public function countForMode(string $mode): int
    {
        if (!array_key_exists($mode, $this->counts)) {
            return 0;
        }
        return $this->counts[$mode];
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'version' => self::VERSION,
            'reach'   => $this->counts,
        ];
    }
}