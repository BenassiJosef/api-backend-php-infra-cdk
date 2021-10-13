<?php


namespace App\Package\Upload;


use App\Models\Organization;
use App\Models\UserProfile;
use App\Models\UserRegistration;
use App\Package\DataSources\CandidateProfile;
use App\Package\Member\PartnerCustomerController;
use Carbon\Carbon;
use DateTime;
use DateTimeImmutable;
use DoctrineExtensions\Query\Mysql\Date;
use Exception;
use JsonSerializable;
use YaLinqo\Enumerable;

/**
 * Class UploadRow
 * @package App\Package\Upload
 */
class UploadRow implements JsonSerializable
{
    /**
     * @var array
     */
    public static $columnHeaders = [
        'email',
        'first',
        'last',
        'phone',
        'dateOfBirth',
        'createdAt'
    ];

    /**
     * @var int | null
     */
    private static $numHeaders = null;

    /**
     * @return int
     */
    public static function numHeaders(): int
    {
        if (self::$numHeaders === null) {
            self::$numHeaders = count(self::$columnHeaders);
        }
        return self::$numHeaders;
    }

    /**
     * @param array $headers
     * @return array
     */
    public static function truncateHeaders(array $headers): array
    {
        if (count($headers) === self::numHeaders()) {
            return $headers;
        }
        return array_slice($headers, 0, self::numHeaders());
    }

    public static function diffHeaders(array $headers): array
    {
        return array_diff(self::truncateHeaders($headers), self::$columnHeaders);
    }

    /**
     * @param array $headers
     * @return bool
     */
    public static function validHeaders(array $headers): bool
    {
        return count(self::diffHeaders($headers)) === 0;
    }

    /**
     * @param array $input
     * @return static
     */
    public static function fromArray(array $input): self
    {
        $row              = new self();
        $row->email       = empty($input['email']) ? null : $input['email'];
        $row->first       = empty($input['first']) ? null : $input['first'];
        $row->last        = empty($input['last']) ? null : $input['last'];
        $row->phone       = empty($input['phone']) ? null : $input['phone'];
        $row->dateOfBirth = empty($input['dateOfBirth']) ? null : $input['dateOfBirth'];
        $row->createdAt   = empty($input['createdAt']) ? null : $input['createdAt'];
        return $row;
    }

    /**
     * @var string $email
     */
    private $email;

    /**
     * @var string $first
     */
    private $first;

    /**
     * @var string $last
     */
    private $last;

    /**
     * @var string $phone
     */
    private $phone;

    /**
     * @var string
     */
    private $dateOfBirth;

    /**
     * @var string $createdAt
     */
    private $createdAt;

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getFirst(): ?string
    {
        return $this->first;
    }

    /**
     * @return string
     */
    public function getLast(): ?string
    {
        return $this->last;
    }

    /**
     * @return string
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @return string
     */
    public function getDateOfBirth(): ?string
    {
        return $this->dateOfBirth;
    }

    /**
     * @return string
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * @return array
     * @throws Exception
     */
    private function birthDates(): array
    {
        if ($this->dateOfBirth === null) {
            return [];
        }
        $immutableDate = DateTimeImmutable::createFromFormat('d/m/Y', $this->dateOfBirth);
        return [
            'birth_month' => (int)$immutableDate->format('m'),
            'birth_day'   => (int)$immutableDate->format('d'),
        ];
    }

    /**
     * @return string
     * @throws Exception
     */
    private function createdAt(): string
    {
        $createdAt = new DateTime();
        if ($this->createdAt !== null) {
            $parsed = DateTime::createFromFormat("d/m/Y H:i:s", $this->createdAt);
            if ($parsed !== false) {
                $createdAt = $parsed;
            }
        }
        return $createdAt->format("Y-m-d H:i:s");
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function jsonSerialize()
    {
        $output = [
            'email' => $this->email,
        ];
        if ($this->first !== null) {
            $output['first'] = $this->first;
        }
        if ($this->last !== null) {
            $output['last'] = $this->last;
        }
        if ($this->phone !== null) {
            $output['phone'] = $this->phone;
        }
        $output              = array_merge($output, $this->birthDates());
        $output['timestamp'] = $this->createdAt();
        return $output;
    }

    public function toCandidateProfile(): CandidateProfile
    {
        $dateOfBirth = null;
        $parsedDateOfBirth = DateTime::createFromFormat('d/m/Y', $this->dateOfBirth);
        if($parsedDateOfBirth !== false){
            $dateOfBirth = $parsedDateOfBirth;
        }
        return new CandidateProfile(
            $this->email,
            $this->first,
            $this->last,
            $this->phone,
            $dateOfBirth,
        );
    }
}