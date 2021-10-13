<?php


namespace App\Package\Async;

use Exception;

class FlushException extends Exception {}

interface Flusher
{
    /**
     * @throws FlushException
     */
    public function flush(): void;
}