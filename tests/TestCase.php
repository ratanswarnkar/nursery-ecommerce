<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.connections.mysql.database') !== 'nursery_ecommerce_test') {
            config(['database.connections.mysql.database' => 'nursery_ecommerce_test']);
            DB::purge('mysql');
        }
    }
}
