<?php
/**
 * Created by jamieaitken on 27/09/2018 at 15:09
 * Copyright © 2018 Captive Ltd. All rights reserved.
 */

namespace App\Controllers\Integrations;


class UserProfileFilter
{

    public function criteriaMet(string $value, string $operator, string $question, array $userProfile)
    {
        if (is_numeric($value)) {
            if (is_int($value)) {
                $value = (int)$value;
            } elseif (is_float($value)) {
                $value = (float)$value;
            }
        } elseif (is_bool($value)) {
            $value = (bool)$value;
        }

        if ($operator === '=') {
            return $userProfile[$question] === $value;

        } elseif ($operator === '<=') {
            return $userProfile[$question] <= $value;

        } elseif ($operator === '>=') {
            return $userProfile[$question] >= $value;

        } elseif ($operator === '>') {
            return $userProfile[$question] > $value;

        } elseif ($operator === '<') {
            return $userProfile[$question] < $value;

        } elseif ($operator === 'contains') {
            return strpos($userProfile[$question], $value) !== false;

        } elseif ($operator === 'notContains') {
            return strpos($userProfile[$question], $value) === false;

        } elseif ($operator === '!=') {
            return $userProfile[$question] !== $value;

        } elseif ($operator === 'startsWith') {
            return substr($userProfile[$question], 0, strlen($value)) === $value;

        } elseif ($operator === 'endsWith') {
            return substr($userProfile[$question], -strlen($value)) === $value;

        } elseif ($operator === 'notStartsWith') {
            return substr($userProfile[$question], 0, strlen($value)) !== $value;

        } elseif ($operator === 'notEndsWith') {
            return substr($userProfile[$question], -strlen($value)) !== $value;

        }
    }

    public function joinCondition(string $conditionType, bool $previousValue, bool $currentValue)
    {
        if ($conditionType === 'OR') {
            return $previousValue || $currentValue;
        } elseif ($conditionType === 'AND') {
            return $previousValue && $currentValue;
        }
    }

    public function groupCriteriaMet(array $statements, array $userProfile)
    {
        $sends      = [];
        $passFilter = false;

        if (count($statements) === 1) {
            $passFilter = $this->criteriaMet($statements[0]['value'], $statements[0]['operand'],
                $statements[0]['question'],
                $userProfile);
        } elseif (count($statements) >= 2) {
            $k1         = 0;
            $k2         = 1;
            $conditions = [];

            /**
             * Evaluate each statement on its own
             */
            foreach ($statements as $key => $statement) {

                if (!isset($statement['operand'])) {
                    continue;
                }

                $sends[] = $this->criteriaMet($statement['value'], $statement['operand'], $statement['question'],
                    $userProfile);
            }


            /**
             * Evaluate the statements in a compound surrounding
             */
            foreach ($statements as $key => $statement) {
                if ($k2 === count($statements)) {
                    continue;
                }

                if (!isset($statement['joinType'])) {
                    continue;
                }

                if ($statement['joinType']) {
                    $conditions[] = $this->joinCondition($statement['joinType'], $sends[$k1], $sends[$k2]);
                }
                $k1++;
                $k2++;
            }

            $passFilter    = true;
            $conditionKey1 = 0;
            $conditionKey2 = 1;
            foreach ($conditions as $conditionKey => $c) {

                if (count($conditions) === 1) {
                    if ($c === false) {
                        $passFilter = false;
                    }
                } else {

                    if (!isset($statements[$conditionKey1]['joinType'])) {
                        continue;
                    }

                    if (!isset($conditions[$conditionKey2])) {
                        continue;
                    }

                    $passFilter = $this->joinCondition($statements[$conditionKey2]['joinType'],
                        $conditions[$conditionKey1], $conditions[$conditionKey2]);
                }

                $conditionKey1++;
                $conditionKey2++;
            }
        }

        return $passFilter;
    }
}