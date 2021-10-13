<?php


namespace App\Package\Profile;

use JsonSerializable;

/**
 * Class StubUserProfile
 * @package App\Package\Profile
 */
class StubUserProfile implements JsonSerializable, MinimalUserProfile
{
    /**
     * StubUserProfile constructor.
     */
    public function __construct()
    {
        $this->id = 1;
    }

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $profile             = new self();
        $profile->id         = $data['id'];
        $profile->first      = $data['first'];
        $profile->last       = $data['last'];
        $profile->email      = $data['email'];
        $profile->phone      = $data['phone'];
        $profile->gender     = $data['gender'];
        $profile->postCode   = $data['postcode'];
        $profile->birthDay   = $data['birthDay'];
        $profile->birthMonth = $data['birthMonth'];
        return $profile;
    }

    /**
     * @var int $id
     */
    private $id;

    /**
     * @var string $first
     */
    private $first;

    /**
     * @var string $last
     */
    private $last;

    /**
     * @var string $email
     */
    private $email;

    /**
     * @var string $phone
     */
    private $phone;

    /**
     * @var string $gender
     */
    private $gender;

    /**
     * @var string $postCode
     */
    private $postCode;

    /**
     * @var int $birthDay
     */
    private $birthDay;

    /**
     * @var int $birthMonth
     */
    private $birthMonth;



    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
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
    public function getEmail(): ?string
    {
        return $this->email;
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
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * @return string
     */
    public function getPostCode(): ?string
    {
        return $this->postCode;
    }

    /**
     * @return int
     */
    public function getBirthDay(): ?int
    {
        return $this->birthDay;
    }

    /**
     * @return int
     */
    public function getBirthMonth(): ?int
    {
        return $this->birthMonth;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize()
    {
        return [
            'id'         => (int)$this->id,
            'first'      => $this->first,
            'last'       => $this->last,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'gender'     => $this->gender,
            'postCode'   => $this->postCode,
            'birthDay'   => $this->birthDay,
            'birthMonth' => $this->birthMonth,
        ];
    }
}