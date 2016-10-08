<?php

namespace sskaje\mitm\ProxyHandler;

use sskaje\mitm\Connection;

class Base
{
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

}