<?php


namespace App\Package\WebForms;


use JsonSerializable;

class EmailSignupInput implements JsonSerializable
{
    public static function fromArray(array $data): self
    {
        $input             = new self();
        $input->email      = $data['email'] ?? null;
        $input->first      = $data['first'] ?? null;
        $input->last       = $data['last'] ?? null;
        $input->phone      = $data['phone'] ?? null;
        $input->birthMonth = $data['birthMonth'] ?? null;
        $input->birthDay   = $data['birthDay'] ?? null;
        $input->gender     = $data['gender'] ?? null;
        return $input;
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
     * @var int | null $birthMonth
     */
    private $birthMonth;

    /**
     * @var int | null $birthDay
     */
    private $birthDay;

    /**
     * @var string | null $gender
     */
    private $gender;

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
     * @return int|null
     */
    public function getBirthMonth(): ?int
    {
        return $this->birthMonth;
    }

    /**
     * @return int|null
     */
    public function getBirthDay(): ?int
    {
        return $this->birthDay;
    }

    /**
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $output = [
            'email'      => $this->getEmail(),
            'first'      => $this->getFirst(),
            'last'       => $this->getLast(),
            'phone'      => $this->getPhone(),
            'birthMonth' => $this->getBirthMonth(),
            'birthDay'   => $this->getBirthDay(),
            'gender'     => $this->getGender(),
        ];
        return $output;
    }
}