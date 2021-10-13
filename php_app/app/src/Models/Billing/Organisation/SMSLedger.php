<?php



namespace App\Models\Billing\Organisation;

use App\Models\Organization;
use DateTime;
use Ramsey\Uuid\UuidInterface;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Ramsey\Uuid\Uuid;

/**
 * Quotes
 *
 * @ORM\Table(name="organization_sms_ledger")
 * @ORM\Entity
 */
class SMSLedger implements JsonSerializable
{
	public function __construct(
		Organization $organization,
		string $reason,
		SMSLedger $previousLedgerItem = null
	) {
		$this->id = Uuid::uuid1();
		$this->organizationId = $organization->getId();
		$this->organization   = $organization;
		$this->createdAt      = new DateTime();
		$this->reason = $reason;
		if (!is_null($previousLedgerItem)) {
			$this->balance = $previousLedgerItem->getBalance();
		}
	}

	/**
	 * @ORM\Id
	 * @ORM\Column(name="id", type="uuid", nullable=false)
	 * @var UuidInterface $id
	 */
	private $id;

	/**
	 * @ORM\Column(name="organization_id", type="uuid", nullable=false)
	 * @var UuidInterface $organizationId
	 */
	private $organizationId;

	/**
	 * @ORM\OneToOne(targetEntity="App\Models\Organization", mappedBy="parent")
	 * @ORM\JoinColumn(name="organization_id", referencedColumnName="id")
	 * @var Organization $organization
	 */
	private $organization;

	/**
	 * @var string
	 * @ORM\Column(name="reason", type="string")
	 */
	private $reason = 0;

	/**
	 * @var int
	 * @ORM\Column(name="debt", type="integer")
	 */
	private $debt = 0;

	/**
	 * @var int
	 * @ORM\Column(name="credit", type="integer")
	 */
	private $credit = 0;

	/**
	 * @var int
	 * @ORM\Column(name="balance", type="integer")
	 */
	private $balance = 0;

	/**
	 * @var DateTime
	 * @ORM\Column(name="created_at", type="datetime")
	 */
	private $createdAt;

	public function addCredit(int $credit)
	{
		$this->balance = $this->balance + $credit;
		$this->credit = $credit;
	}

	public function deductCredit(int $debt)
	{
		$this->balance = $this->balance - $debt;
		$this->debt = $debt;
	}

	public function getBalance(): int
	{
		return $this->balance;
	}

	public function getDebt(): int
	{
		return $this->debt;
	}

	public function getCredit(): int
	{
		return $this->credit;
	}

	public function canUseCredits(int $credits): bool
	{
		return ($this->balance - $credits) >= 0;
	}

	public function jsonSerialize()
	{
		return [
			"id" => $this->id->toString(),
			"credit" => $this->getCredit(),
			"debt"          => $this->getDebt(),
			"balance" => $this->getBalance(),
			"created_at"       => $this->createdAt,
			"reason" => $this->reason
		];
	}
}
