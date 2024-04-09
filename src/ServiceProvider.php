<?php

namespace Trendyminds\Orbit;

use Statamic\Providers\AddonServiceProvider;
use Trendyminds\Orbit\Commands\OrbitSync;

class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon()
    {
        $this->commands([
            OrbitSync::class,
        ]);
    }
}
