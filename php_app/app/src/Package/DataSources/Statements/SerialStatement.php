<?php


namespace App\Package\DataSources\Statements;

use App\Package\DataSources\Statement;

use App\Models\DataSources\Interaction;

final class SerialStatement implements Statement
{
    /**
     * @var Interaction $interaction
     */
    private $interaction;

    /**
     * @var string[] $serials
     */
    private $serials;

    /**
     * SerialStatement constructor.
     * @param Interaction $interaction
     * @param string[] $serials
     */
    public function __construct(Interaction $interaction, array $serials)
    {
        $this->interaction = $interaction;
        $this->serials     = $serials;
    }

    public function statement(): string
    {
        $statement    = 'INSERT INTO `interaction_serial` (`id`, `interaction_id`, `serial`) VALUES ';
        $keyedSerials = $this->keyedSerials();
        $keys         = array_keys($keyedSerials);
        $values       = [];
        foreach ($keys as $key) {
            $values[] = "(UUID(), :interaction_id, :$key)";
        }
        $statement .= implode(', ', $values);
        $statement .= ';';
        return $statement;
    }

    public function arguments(): array
    {
        return array_merge(
            [
                'interaction_id' => $this->interaction->getId()->toString(),
            ], $this->keyedSerials()
        );
    }

    private function keyedSerials(): array
    {
        $keyed = [];
        foreach ($this->serials as $i => $serial) {
            $keyed["serial_$i"] = $serial;
        }
        return $keyed;
    }
}