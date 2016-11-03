<?php

declare(strict_types=1);

/*
 * This file is part of Code York Connect Four.
 *
 * (c) Graham Campbell
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CodeYork\Tests\ConnectFour;

use CodeYork\ConnectFour\AppServiceProvider;
use CodeYork\ConnectFour\Engine;
use GrahamCampbell\TestBenchCore\LaravelTrait;
use GrahamCampbell\TestBenchCore\ServiceProviderTrait;

class ServiceProviderTest extends AbstractTestCase
{
    use LaravelTrait, ServiceProviderTrait;

    protected function getServiceProviderClass($app)
    {
        return AppServiceProvider::class;
    }

    public function testEngineIsInjectable()
    {
        $this->assertIsInjectable(Engine::class);
    }
}
