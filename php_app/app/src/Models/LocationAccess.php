<?php


namespace App\Models;

use App\Models\Locations\LocationSettings;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Class LocationAccess
 *
 * @ORM\Table(name="location_access")
 * @ORM\Entity
 * @package App\Models
 */
class LocationAccess implements JsonSerializable
{
	/**
	 * @ORM\Id
	 * @ORM\Column(name="serial", type="string", length=12, nullable=false)
	 * @var string $serial
	 */
	private $serial;

	/**
	 * @ORM\Id
	 * @ORM\Column(name="user_id", type="uuid")
	 * @var UuidInterface $userId
	 */
	private $userId;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\OauthUser")
	 * @ORM\JoinColumn(name="user_id", referencedColumnName="uid", nullable=false)
	 * @var OauthUser $user
	 */
	private $user;

	/**
	 * @ORM\Id
	 * @ORM\Column(name="role_id", type="uuid")
	 * @var UuidInterface $roleId
	 */
	private $roleId;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Models\Role")
	 * @ORM\JoinColumn(name="role_id", referencedColumnName="id", nullable=false)
	 * @var Role $role
	 */
	private $role;

	/**
	 * LocationAccess constructor.
	 * @param LocationSettings $location
	 * @param OauthUser $user
	 * @param Role $role
	 */
	public function __construct(LocationSettings $location, OauthUser $user, Role $role)
	{
		$this->serial = $location->getSerial();

		$this->userId = $user->getUid();
		$this->user   = $user;

		$this->roleId = $role->getId();
		$this->role   = $role;
	}

	/**
	 * @return string
	 */
	public function getSerial(): string
	{
		return $this->serial;
	}

	/**
	 * @return OauthUser
	 */
	public function getUser(): OauthUser
	{
		return $this->user;
	}

	/**
	 * @return Role
	 */
	public function getRole(): Role
	{
		return $this->role;
	}

	/**
	 * @return array|mixed
	 */
	public function jsonSerialize()
	{
		return [
			'serial' => $this->getSerial(),
			'userId' => $this->getUser()->getUid(),
			'role'   => $this->getRole()
		];
	}
}
