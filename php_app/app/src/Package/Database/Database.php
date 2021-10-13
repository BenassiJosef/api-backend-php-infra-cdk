<?php

namespace App\Package\Database;

/**
 * Interface Database
 * @package App\Package\Database
 */
interface Database extends RowFetcher, SingleResultFetcher, FirstColumnFetcher, Executor, Transaction
{
}