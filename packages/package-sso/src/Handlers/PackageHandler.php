<?php

declare(strict_types=1);

namespace Fisher\SSO\Handlers;

use Illuminate\Console\Command;

class PackageHandler extends \App\Support\PackageHandler
{
    /**
     * Publish public asstes source handle.
     *
     * @param \Illuminate\Console\Command $command
     * @return mixed
     */
    public function publishAsstesHandle(Command $command)
    {
        $force = $command->confirm('Overwrite any existing files');

        return $command->call('vendor:publish', [
            '--provider' => \Fisher\SSO\Providers\AppServiceProvider::class,
            '--tag' => 'package-sso-public',
            '--force' => boolval($force),
        ]);
    }

    /**
     * Publish package config source handle.
     *
     * @param \Illuminate\Console\Command $command
     * @return mixed
     */
    public function publishConfigHandle(Command $command)
    {
        $force = $command->confirm('Overwrite any existing files');

        return $command->call('vendor:publish', [
            '--provider' => \Fisher\SSO\Providers\AppServiceProvider::class,
            '--tag' => 'package-sso-config',
            '--force' => boolval($force),
        ]);
    }

    /**
     * Publish package resource handle.
     *
     * @param \Illuminate\Console\Command $command
     * @return mixed
     */
    public function publishHandle(Command $command)
    {
        return $command->call('vendor:publish', [
            '--provider' => \Fisher\SSO\Providers\AppServiceProvider::class,
        ]);
    }

    /**
     * The migrate handle.
     *
     * @param \Illuminate\Console\Command $command
     * @return mixed
     */
    public function migrateHandle(Command $command)
    {
        return $command->call('migrate');
    }

    /**
     * The DB seeder handler.
     *
     * @param \Illuminate\Console\Command $command
     * @return mixed
     */
    public function dbSeedHandle(Command $command)
    {
        return $command->call('db:seed', [
            '--class' => \Fisher\SSO\Seeds\DatabaseSeeder::class,
        ]);
    }
}
