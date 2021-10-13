<?php

namespace App\Utils\Formatters;

abstract class JsonDeserializer
{
    /**
     * @param string|array $json
     * @return $this
     */
    public static function deserialize($json)
    {
        $className = get_called_class();
        $classInstance = new $className();
        if (is_string($json)) {
            $json = json_decode($json);
        }

        foreach ($json as $key => $value) {
            $camelKey = self::camelCase($key);
            if (!array_key_exists($key, $classInstance->jsonSerialize())) {
                continue;
            }
            $classInstance->{$camelKey} = $value;
        }

        return $classInstance;
    }
    /**
     * @param string $json
     * @return $this[]
     */
    public static function deserializeArray($json)
    {
        $json = json_decode($json);
        $items = [];
        foreach ($json as $item) {
            $items[] = self::Deserialize($item);
        }

        return $items;
    }

    private static function camelCase(string $str)
    {
        $i = array("-", "_");
        $str = preg_replace('/([a-z])([A-Z])/', "\\1 \\2", $str);
        $str = preg_replace('@[^a-zA-Z0-9\-_ ]+@', '', $str);
        $str = str_replace($i, ' ', $str);
        $str = str_replace(' ', '', ucwords(strtolower($str)));
        $str = strtolower(substr($str, 0, 1)) . substr($str, 1);
        return $str;
    }
}
