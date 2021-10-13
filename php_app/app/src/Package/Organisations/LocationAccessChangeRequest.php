<?php


namespace App\Package\Organisations;


use App\Models\OauthUser;
use App\Models\Role;
use JsonSerializable;

/**
 * Class LocationAccessChangeRequest
 * @package App\Package\Organisations
 */
class LocationAccessChangeRequest implements JsonSerializable
{
	/**
	 * @var OauthUser $admin
	 */
	private $admin;

	/**
	 * @var OauthUser $subject
	 */
	private $subject;

	/**
	 * @var Role $role
	 */
	private $role;

	/**
	 * @var string[] $serials
	 */
	private $serials = [];

	/**
	 * LocationAccessChangeRequest constructor.
	 * @param OauthUser $admin
	 * @param OauthUser $subject
	 * @param Role $role
	 * @param string[] $serials
	 */
	public function __construct(OauthUser $admin, OauthUser $subject, Role $role, array $serials)
	{
		$this->admin   = $admin;
		$this->subject = $subject;
		$this->role    = $role;
		$this->serials = $serials;
	}

	/**
	 * @return OauthUser
	 */
	public function getAdmin(): OauthUser
	{
		return $this->admin;
	}

	/**
	 * @return OauthUser
	 */
	public function getSubject(): OauthUser
	{
		return $this->subject;
	}

	/**
	 * @return Role
	 */
	public function getRole(): Role
	{
		return $this->role;
	}

	/**
	 * @return string[]
	 */
	public function getSerials(): array
	{
		return $this->serials;
	}

	public function jsonSerialize()
	{
		return [
			'role' => $this->getRole(),
			'access' => $this->getSerials(),
		];
	}
}
