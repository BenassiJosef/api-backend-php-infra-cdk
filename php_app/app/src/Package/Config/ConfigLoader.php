<?php


namespace App\Package\Config;


use App\Package\Config\Exceptions\FailedToLoadEnvironmentVarException;

/**
 * Class ConfigLoader
 * @package App\Package\Config
 */
class ConfigLoader
{
    /**
     * @param string $name
     * @param string|null $override
     * @param string|null $default
     * @return string
     * @throws \App\Package\Config\Exceptions\FailedToLoadEnvironmentVarException
     */
    public static function coalesceEnvString(
        string $name,
        ?string $override = null,
        ?string $default = null
    ): string {
        if ($override !== null) {
            return $override;
        }
        $var = getenv($name);
        if ($var === false) {
            if ($default === null) {
                throw new FailedToLoadEnvironmentVarException($name);
            }
            return $default;
        }
        return $var;
    }
}