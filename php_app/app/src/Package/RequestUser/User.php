<?php


namespace App\Package\RequestUser;

use Cassandra\Date;
use DateTime;
use JsonSerializable;

/**
 * Class User
 * @package App\Package\RequestUser
 */
class User implements JsonSerializable
{

    /**
     * @var array
     */
    private static $mapping = [
        'uid'         => 'setUid',
        'admin'       => 'setAdmin',
        'reseller'    => 'setReseller',
        'email'       => 'setEmail',
        'company'     => 'setCompany',
        'first'       => 'setFirstName',
        'last'        => 'setLastName',
        'inChargeBee' => 'setIsChargebee',
        'role'        => 'setRole',
        'country'     => 'setCountry',
        'created'     => 'setCreated',
        'edited'      => 'setEdited',
        'access'      => 'setAccess'
    ];

    /**
     * @var string $uid
     */
    private $uid;

    /**
     * @var string $admin
     */
    private $admin;

    /**
     * @var string $reseller
     */
    private $reseller;

    /**
     * @var string $email
     */
    private $email;

    /**
     * @var string $company
     */
    private $company;

    /**
     * @var string $firstName
     */
    private $firstName;

    /**
     * @var string $lastName
     */
    private $lastName;

    /**
     * @var bool $isChargebee
     */
    private $isChargebee = true;

    /**
     * @var int $role
     */
    private $role;

    /**
     * @var string $country
     */
    private $country = "gb";

    /**
     * @var DateTime $created
     */
    private $created;

    /**
     * @var DateTime $edited
     */
    private $edited;

    /**
     * @var []string $access
     */
    private $access = [];

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->created = new DateTime();
        $this->edited = new DateTime();
    }

    /**
     * @param array $data
     * @return static
     * @throws \Exception
     */
    public static function createFromArray(array $data): self
    {
        $user = new self();
        foreach (self::$mapping as $field => $method){
            if (!array_key_exists($field, $data)){
                continue;
            }
            $value = $data[$field];
            try {
                $user->$method($value);
            }catch (\Throwable $exception){
                throw new \Exception(
                    "could not marshal value ($value), int field ($field)",
                    0,
                    $exception
                );
            }
        }
        return $user;
    }

    /**
     * @param string $uid
     * @return User
     */
    public function setUid(string $uid): User
    {
        $this->uid = $uid;
        return $this;
    }

    /**
     * @param string $admin
     * @return User
     */
    private function setAdmin(?string $admin): User
    {
        $this->admin = $admin;
        return $this;
    }

    /**
     * @param string $reseller
     * @return User
     */
    private function setReseller(?string $reseller): User
    {
        $this->reseller = $reseller;
        return $this;
    }

    /**
     * @param string $email
     * @return User
     */
    private function setEmail(string $email): User
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param string $company
     * @return User
     */
    private function setCompany(?string $company): User
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @param string $firstName
     * @return User
     */
    private function setFirstName(?string $firstName): User
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @param string $lastName
     * @return User
     */
    private function setLastName(?string $lastName): User
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @param bool $isChargebee
     * @return User
     */
    private function setIsChargebee(bool $isChargebee): User
    {
        $this->isChargebee = $isChargebee;
        return $this;
    }

    /**
     * @param int $role
     * @return User
     */
    private function setRole(int $role): User
    {
        $this->role = $role;
        return $this;
    }

    /**
     * @param string $country
     * @return User
     */
    private function setCountry(string $country): User
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @param DateTime $created
     * @return User
     */
    private function setCreated(DateTime $created): User
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @param DateTime $edited
     * @return User
     */
    private function setEdited(?DateTime $edited): User
    {
        $this->edited = $edited;
        return $this;
    }

    /**
     * @param array $access
     * @return User
     */
    private function setAccess(array $access)
    {
        $this->access = $access;
        return $this;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * @return string
     */
    public function getAdmin(): ?string
    {
        return $this->admin;
    }

    /**
     * @return string
     */
    public function getReseller(): ?string
    {
        return $this->reseller;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @return string|null
     */
    public function getFullName(): ?string
    {
        $name = "";
        $first = $this->getFirstName();
        if ($first !== null){
            $name = "$first ";
        }
        $last = $this->getLastName();
        if ($last !== null){
            $name .= $last;
        }
        if ($name === ""){
            return null;
        }
        return $name;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @return bool
     */
    public function isChargebee(): bool
    {
        return $this->isChargebee;
    }

    /**
     * @return int
     */
    public function getRole(): int
    {
        return $this->role;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @return DateTime
     */
    public function getEdited(): ?DateTime
    {
        return $this->edited;
    }

    /**
     * @return mixed
     */
    public function getAccess(): array
    {
        return $this->access;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'uid'         => $this->getUid(),
            'admin'       => $this->getAdmin(),
            'reseller'    => $this->getReseller(),
            'email'       => $this->getEmail(),
            'company'     => $this->getCompany(),
            'first'       => $this->getFirstName(),
            'last'        => $this->getLastName(),
            'isChargeBee' => $this->isChargebee(),
            'role'        => $this->getRole(),
            'country'     => $this->getCountry(),
            'created'     => $this->getCreated(),
            'edited'      => $this->getEdited(),
            'access'      => $this->getAccess()
        ];
    }
}