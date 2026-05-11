<?php

namespace Osoobe\Laravel\Settings\Tests;

use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Osoobe\Laravel\Settings\LaravelSettingsServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelSettingsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        // Required for encrypt()/decrypt() used by secret/setSecret
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->createPackageTables();
        $this->createFixtureTables();
    }

    /**
     * Create the package tables directly, bypassing the migration that requires
     * doctrine/dbal for the column-alter step (not installable against Laravel 8).
     * The schema here reflects the final state after all migrations have run.
     */
    protected function createPackageTables(): void
    {
        Schema::create('app_metas', function ($table) {
            $table->id();
            $table->string('meta_key')->unique();
            $table->string('meta_type')->nullable();
            $table->longText('meta_value')->nullable();
            $table->string('category')->nullable()->default('default')->index();
            $table->json('data')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('model_metas', function ($table) {
            $table->id();
            $table->morphs('model');
            $table->string('meta_key');
            $table->string('meta_type')->nullable();
            $table->longText('meta_value')->nullable();
            $table->string('category')->nullable()->default('default')->index();
            $table->json('data')->nullable();
            $table->timestamps();
            $table->unique(['model_id', 'model_type', 'meta_key']);
        });
    }

    protected function createFixtureTables(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }
}
