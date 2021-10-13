<?php


namespace App\Package\Member;


use App\Models\OauthUser;
use DateTime;
use InvalidArgumentException;
use Throwable;

class UserCreationInput
{
    /**
     * @var string | null $admin
     */
    private $admin;

    /**
     * @var string | null $reseller
     */
    private $reseller;

    /**
     * @var string $email
     */
    private $email;

    /**
     * @var string | null $password
     */
    private $password;

    /**
     * @var string | null $company
     */
    private $company;

    /**
     * @var string | null $organisationId
     */
    private $organisationId;

    /**
     * @var string | null $parentOrganizationId
     */
    private $parentOrganisationId;

    /**
     * @var string $first
     */
    private $first;

    /**
     * @var string $last
     */
    private $last;

    /**
     * @var int $role
     */
    private $role;

    /**
     * @var string $country
     */
    private $country;

    /**
     * @var bool $shouldCreateChargebeeCustomer
     */
    private $shouldCreateChargebeeCustomer;

    public function updateUser(OauthUser $user)
    {
        $user
            ->setEmail($this->email ?? $user->getEmail())
            ->setCompany($this->company ?? $user->getCompany())
            ->setReseller($this->reseller ?? $user->getReseller())
            ->setFirst($this->first ?? $user->getFirst())
            ->setLast($this->last ?? $user->getLast())
            ->setRole($this->role ?? $user->getRole())
            ->setCompany($this->company ?? $user->getCompany())
            ->setEdited(new DateTime());

        if (!is_null($this->password)) {
            $user->setPassword($this->password);
        }
    }

    public static function createFromArray(array $input): self
    {
        $map  = [
            'admin'                         => 'setAdmin',
            'reseller'                      => 'setReseller',
            'email'                         => 'setEmail',
            'password'                      => 'setPassword',
            'company'                       => 'setCompany',
            'organisationId'                => 'setOrganisationId',
            'parentOrganisationId'          => 'setParentOrganisationId',
            'first'                         => 'setFirst',
            'last'                          => 'setLast',
            'role'                          => 'setRole',
            'country'                       => 'setCountry',
            'shouldCreateChargebeeCustomer' => 'setShouldCreateChargebeeCustomer',
        ];
        $user = new self();
        foreach ($input as $field => $val) {
            if (!array_key_exists($field, $map)) {
                throw new InvalidArgumentException("Unknown property ($field) in input");
            }
            try {
                $method = $map[$field];
                $user->$method($val);
            } catch (Throwable $t) {
                throw new InvalidArgumentException("cannot set ($val) as ($field)");
            }
        }

        return $user;
    }

    /**
     * @return string|null
     */
    public function getParentOrganisationId(): ?string
    {
        return $this->parentOrganisationId;
    }

    /**
     * @param string|null $parentOrganisationId
     * @return UserCreationInput
     */
    public function setParentOrganisationId(?string $parentOrganisationId): UserCreationInput
    {
        $this->parentOrganisationId = $parentOrganisationId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAdmin(): ?string
    {
        return $this->admin;
    }

    /**
     * @return string | null
     */
    public function getReseller(): ?string
    {
        return $this->reseller;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * @return string|null
     */
    public function getOrganisationId(): ?string
    {
        return $this->organisationId;
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
     * @return int
     */
    public function getRole(): ?int
    {
        return $this->role;
    }

    /**
     * @return string
     */
    public function getCountry(): ?string
    {
        return $this->country;
    }

    /**
     * @param string | null $admin
     * @return UserCreationInput
     */
    private function setAdmin(?string $admin): UserCreationInput
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * @param string | null $reseller
     * @return UserCreationInput
     */
    public function setReseller(?string $reseller): UserCreationInput
    {
        $this->reseller = $reseller;

        return $this;
    }

    /**
     * @param string $email
     * @return UserCreationInput
     */
    private function setEmail(?string $email): UserCreationInput
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @param string|null $password
     * @return UserCreationInput
     */
    private function setPassword(?string $password): UserCreationInput
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @param string|null $company
     * @return UserCreationInput
     */
    private function setCompany(?string $company): UserCreationInput
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @param string|null $organisationId
     * @return UserCreationInput
     */
    private function setOrganisationId(?string $organisationId): UserCreationInput
    {
        $this->organisationId = $organisationId;

        return $this;
    }

    /**
     * @param string $first
     * @return UserCreationInput
     */
    private function setFirst(?string $first): UserCreationInput
    {
        $this->first = $first;

        return $this;
    }

    /**
     * @param string $last
     * @return UserCreationInput
     */
    private function setLast(?string $last): UserCreationInput
    {
        $this->last = $last;

        return $this;
    }

    /**
     * @param int $role
     * @return UserCreationInput
     */
    private function setRole(?int $role): UserCreationInput
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @param string $country
     * @return UserCreationInput
     */
    private function setCountry(?string $country): UserCreationInput
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @param bool $shouldCreateChargebeeCustomer
     * @return UserCreationInput
     */
    private function setShouldCreateChargebeeCustomer(bool $shouldCreateChargebeeCustomer): UserCreationInput
    {
        $this->shouldCreateChargebeeCustomer = $shouldCreateChargebeeCustomer;

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldCreateChargebeeCustomer(): bool
    {
        return $this->shouldCreateChargebeeCustomer;
    }
}
