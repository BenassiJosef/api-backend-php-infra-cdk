<?php


namespace App\Package\DataSources;


use DateTime;
use Exception;
use JsonSerializable;
use DateTimeImmutable;
use Slim\Http\Request;

class CandidateProfile implements JsonSerializable
{
    /**
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request): self
    {
        return self::fromArray(
            $request->getParsedBody()
        );
    }

    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        $profile          = new self(
            $data['email'],
        );
        $profile->first   = $data['first'] ?? null;
        $profile->last    = $data['last'] ?? null;
        $profile->phone   = $data['phone'] ?? null;
        $profile->country = $data['country'] ?? null;
        $profile->gender  = $data['country'] ?? null;
        if (isset($data['birthMonth']) && isset($data['birthDay'])) {
            $birthMonth  = $data['birthMonth'];
            $birthDay    = $data['birthDay'];
            $dateOfBirth = DateTimeImmutable::createFromFormat('Y-m-d', "1970-$birthMonth-$birthDay");
            if ($dateOfBirth !== false) {
                $profile->dateOfBirth = DateTime::createFromImmutable($dateOfBirth);
            }
        }
        $profile->optInStatuses = OptInStatuses::fromArray($data);
        return $profile;
    }

    private static function boolToDateTime(bool $opted): ?DateTime
    {
        if ($opted) {
            return new DateTime();
        }
        return null;
    }

    private static function dateTimeToBool(?DateTime $opted): bool
    {
        if ($opted !== null) {
            return true;
        }
        return false;
    }

    /**
     * @var string $email
     */
    private $email;

    /**
     * @var string | null $first
     */
    private $first;

    /**
     * @var string | null $last
     */
    private $last;

    /**
     * @var string | null $phone
     */
    private $phone;

    /**
     * @var DateTime | null $dateOfBirth
     */
    private $dateOfBirth;

    /**
     * @var string | null $country
     */
    private $country;

    /**
     * @var string | null $gender
     */
    private $gender;

    /**
     * @var OptInStatuses $optInStatuses
     */
    private $optInStatuses;

    /**
     * CandidateProfile constructor.
     * @param string $email
     * @param string|null $first
     * @param string|null $last
     * @param string|null $phone
     * @param DateTime|null $dateOfBirth
     * @param string|null $country
     * @param string|null $gender
     * @param OptInStatuses|null $optInStatuses
     */
    public function __construct(
        string $email,
        ?string $first = null,
        ?string $last = null,
        ?string $phone = null,
        ?DateTime $dateOfBirth = null,
        ?string $country = null,
        ?string $gender = null,
        OptInStatuses $optInStatuses = null
    ) {
        $this->email       = $email;
        $this->first       = $first;
        $this->last        = $last;
        $this->phone       = $phone;
        $this->dateOfBirth = $dateOfBirth;
        $this->country     = $country;
        $this->gender      = $gender;
        if ($optInStatuses === null) {
            $optInStatuses = new OptInStatuses();
        }
        $this->optInStatuses = $optInStatuses;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getFirst(): ?string
    {
        return $this->first;
    }

    /**
     * @return string|null
     */
    public function getLast(): ?string
    {
        return $this->last;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @return DateTime|null
     */
    public function getDateOfBirth(): ?DateTime
    {
        return $this->dateOfBirth;
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * @param OptInStatuses $optInStatuses
     * @return CandidateProfile
     */
    public function setOptInStatuses(OptInStatuses $optInStatuses): CandidateProfile
    {
        $this->optInStatuses = $optInStatuses;
        return $this;
    }

    /**
     * @return OptInStatuses
     */
    public function getOptInStatuses(): OptInStatuses
    {
        return $this->optInStatuses;
    }

    /**
     * @return array
     * @throws Exception
     */
    private function birthDates(): array
    {
        if ($this->dateOfBirth === null) {
            return [
                'birth_month' => null,
                'birth_day'   => null,
            ];
        }
        return [
            'birth_month' => (int)$this->dateOfBirth->format('m'),
            'birth_day'   => (int)$this->dateOfBirth->format('d'),
        ];
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function jsonSerialize()
    {
        return array_merge(
            [
                'email'    => $this->email,
                'first'    => $this->first,
                'last'     => $this->last,
                'country'  => $this->country,
                'postcode' => $this->country,
                'gender'   => $this->gender,
                'phone'    => $this->phone,
            ],
            $this->birthDates(),
            [
                'verified_id' => md5($this->email),
                'timestamp'   => (new DateTime())->format("Y-m-d H:i:s"),
            ]
        );
    }
}