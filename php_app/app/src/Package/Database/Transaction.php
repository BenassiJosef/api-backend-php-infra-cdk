<?php


namespace App\Package\Database;


interface Transaction
{
    public function beginTransaction();

    public function commit();

    public function rollback();
}