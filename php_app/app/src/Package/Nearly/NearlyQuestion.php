<?php

namespace App\Package\Nearly;

use JsonSerializable;

class NearlyQuestion implements JsonSerializable
{


    public function __construct(
        string $question,
        bool $visible
    ) {

        $this->questions = $question;
        $this->visible = $visible;
    }

    /**
     * @var string $question
     */
    private $question;

    /**
     * @var string $visible
     */
    private $visible;

    /**
     * @var string $text
     */
    private $text;

    /**
     * @var bool $required
     */
    private $required;

    /**
     * @var bool $defaultValue
     */
    private $defaultValue;


    public function getVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible)
    {
        $this->visible = $visible;
    }

    public function setText(string $text)
    {
        $this->text = $text;
    }

    public function setRequired(bool $required)
    {
        $this->required = $required;
    }

    public function setDefaultValue(bool $defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }


    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return [
            'question'                      => $this->question,
            'visible'               => $this->visible,
            'text' => $this->text,
            'required' => $this->required,
            'default_value' => $this->defaultValue
        ];
    }
}
