<?php


namespace App\Package\DataSources;


interface Statement
{
    public function statement(): string;

    public function arguments(): array;
}