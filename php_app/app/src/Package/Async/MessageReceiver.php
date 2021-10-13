<?php


namespace App\Package\Async;


interface MessageReceiver
{
    /**
     * @return Generator | Message[]
     */
    public function messages();
}