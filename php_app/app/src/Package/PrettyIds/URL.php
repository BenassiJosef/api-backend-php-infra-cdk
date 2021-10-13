<?php


namespace App\Package\PrettyIds;


use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Tuupola\Base62;

class URL implements IDPrettyfier
{
    /**
     * @var Base62 $base62
     */
    private $base62;

    /**
     * URL constructor.
     */
    public function __construct()
    {
        $this->base62 = new Base62();
    }

    /**
     * @param UuidInterface $uuid
     * @return string
     */
    public function prettify(UuidInterface $uuid): string
    {
        $hex = str_replace('-', '', $uuid->toString());
        return $this->base62->encode(hex2bin($hex));
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

    private function unprettyId(string $id): UuidInterface
    {
        $bin    = $this->base62->decode($id);
        $hex    = bin2hex($bin);
        $padded = str_pad($hex, 32, '0', STR_PAD_LEFT);
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