<?php


namespace App\Package\PrettyIds;


use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Stringy\Stringy;

class HumanReadable implements IDPrettyfier
{
    /**
     * @var int $partLength
     */
    private $partLength;

    /**
     * @var int $base
     */
    private $base;

    /**
     * @var string $separator
     */
    private $separator;

    /**
     * HumanReadable constructor.
     * @param int $partLength
     * @param int $base
     * @param string $separator
     */
    public function __construct(
        int $partLength = 5,
        int $base = 36,
        string $separator = '-'
    ) {
        $this->partLength = $partLength;
        $this->base       = $base;
        $this->separator  = $separator;
    }


    /**
     * @param UuidInterface $uuid
     * @return string
     */
    public function prettify(UuidInterface $uuid): string
    {
        $hex       = str_replace('-', '', $uuid->toString());
        $shortened = BaseChange::convert($hex, 16, 36);
        $maxChars  = BaseChange::maxCharsForBaseDigits(32, 16, 36);
        $upperCase = strtoupper($shortened);
        $padded    = str_pad($upperCase, $maxChars, "0", STR_PAD_LEFT);
        $parts     = str_split($padded, $this->partLength);
        return implode($this->separator, $parts);
    }


    /**
     * @param string $id
     * @return UuidInterface
     */
    public function unpretty(string $id): UuidInterface
    {
        try {
            $uuid = Uuid::fromString($id);
        } catch (\Throwable $exception) {
            $uuid = $this->unprettyId($id);
        }
        return $uuid;
    }

    private function unprettyId(string $id)
    {
        $prettyBased = str_replace($this->separator, '', $id);
        $lowerCased  = strtolower($prettyBased);
        $hex         = BaseChange::convert($lowerCased, $this->base, 16);
        $padded      = str_pad($hex, 32, '0', STR_PAD_LEFT);

        // 8, 4, 4, 4, 12
        $parts = [
            substr($padded, 0, 8),
            substr($padded, 8, 4),
            substr($padded, 12, 4),
            substr($padded, 16, 4),
            substr($padded, 20, 12)
        ];
        return Uuid::fromString(implode('-', $parts));
    }

}