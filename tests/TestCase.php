<?php

namespace Splicewire\Composition\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Schemastud\DataSchemas\LaravelDataSchemasServiceProvider;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Splicewire\Composition\CompositionDataServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            LaravelDataSchemasServiceProvider::class,
            CompositionDataServiceProvider::class,
        ];
    }
}
