<?php


namespace App\Package\Loyalty\Events;


class RouterSingleton
{
    /**
     * @var Router | null $router
     */
    private static $router;

    public static function getRouter(): Router
    {
        if (self::$router === null) {
            self::$router = new Router();
        }
        return self::$router;
    }
}