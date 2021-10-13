<?php
/**
 * Created by jamieaitken on 27/09/2018 at 12:18
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Models\Integrations;

use Doctrine\ORM\Mapping as ORM;

/**
 * IntegrationEventCriteria
 *
 * @ORM\Table(name="integration_event_criteria")
 * @ORM\Entity
 */
class IntegrationEventCriteria
{

    public function __construct(
        string $filterListId,
        ?string $question,
        ?string $operand,
        ?string $value,
        ?string $joinType,
        ?int $position
    ) {
        $this->filterListId = $filterListId;
        $this->question     = $question;
        $this->operand      = $operand;
        $this->value        = $value;
        $this->joinType     = $joinType;
        $this->position     = $position;
    }

    /**
     * @var string
     * @ORM\Column(name="id", type="string", length=36)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="App\Utils\CustomId")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="filterListId", type="string")
     */
    private $filterListId;

    /**
     * @var string
     * @ORM\Column(name="question", type="string", nullable=true)
     */
    private $question;

    /**
     * @var string
     * @ORM\Column(name="operand", type="string", nullable=true)
     */
    private $operand;

    /**
     * @var string
     * @ORM\Column(name="value", type="string", nullable=true)
     */
    private $value;

    /**
     * @var string
     * @ORM\Column(name="joinType", type="string", nullable=true)
     */
    private $joinType;

    /**
     * @var integer
     * @ORM\Column(name="position", type="integer", nullable=true)
     */
    private $position;

    /**
     * @return array
     */

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function __get($property)
    {
        return $this->$property;
    }

    public function __set($property, $value)
    {
        $this->$property = $value;
    }
}