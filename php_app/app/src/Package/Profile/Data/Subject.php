<?php


namespace App\Package\Profile\Data;


use App\Models\UserProfile;
use App\Package\Database\Database;
use App\Package\Database\Exceptions\FailedToExecuteStatementException;
use App\Package\Database\Exceptions\UnsupportedParamTypeException;
use App\Package\Database\RawStatementExecutor;
use App\Package\Profile\Data\Presentation\DataObject;
use App\Package\Profile\Data\Presentation\Section;
use Doctrine\ORM\EntityManager;
use JsonSerializable;

/**
 * Class Subject
 * @package App\Package\Profile\Data
 */
class Subject implements JsonSerializable
{
    /**
     * @var UserProfile $userProfile
     */
    private $userProfile;

    /**
     * Subject constructor.
     * @param UserProfile $userProfile
     */
    public function __construct(
        UserProfile $userProfile
    ) {
        $this->userProfile    = $userProfile;
    }


    /**
     * @return int
     */
    public function getProfileId(): int
    {
        return $this->userProfile->getId();
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
            'id' => $this->getProfileId(),
        ];
    }
}