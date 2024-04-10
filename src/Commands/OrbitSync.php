<?php

namespace Trendyminds\Orbit\Commands;

use Facades\Statamic\Marketplace\Marketplace;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\Addon;
use Statamic\Statamic;

class OrbitSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orbit:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync application data to Orbit';

    /**
     * The Composer instance.
     *
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Composer $composer)
    {
        parent::__construct();

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $addons = Addon::all();

        $info = [
            'orbit_version' => '1.0.0',
            'type' => 'statamic',
            'has_cms_update' => Marketplace::statamic()->changelog()->availableUpdatesCount() > 0,
            'has_addons_update' => $addons
                ->filter(fn ($addon) => $addon->latestVersion() && $addon->version() !== $addon->latestVersion())
                ->isNotEmpty(),
            'app' => [
                'environment' => $this->laravel->environment(),
                'app_name' => config('app.name'),
                'url' => config('app.url'),
                'laravel_version' => $this->laravel->version(),
                'statamic_version' => Statamic::version().' '.(Statamic::pro() ? 'Pro' : 'Solo'),
                'php_version' => phpversion(),
                'composer_version' => $this->composer->getVersion() ?? 'N/A',
                'debug_mode' => config('app.debug'),
                'maintenance_mode' => $this->laravel->isDownForMaintenance(),
                'ray_enabled' => env('RAY_ENABLED'),
            ],
            'statamic' => [
                'antlers' => config('statamic.antlers.version'),
                'addons' => $addons->count(),
                'stache_watcher' => config('statamic.stache.watcher') ? true : false,
                'static_caching' => config('statamic.static_caching.strategy') ? true : false,
            ],
            'drivers' => [
                'cache' => config('cache.default'),
                'database' => config('database.default'),
                'mail' => config('mail.default'),
                'queue' => config('queue.default'),
                'scout' => config('scout.driver'),
                'session' => config('session.driver'),
            ],
            'addons' => $addons->map(fn ($addon) => [
                'name' => $addon->name(),
                'package' => $addon->package(),
                'version' => $addon->version(),
                'latest_version' => $addon->latestVersion() && $addon->version() !== $addon->latestVersion()
                        ? $addon->latestVersion()
                        : null,
            ])->sortBy('name')->values()->toArray(),
        ];

        try {
            Http::acceptJson()->post('https://orbit.trendyminds.com/api/transmit', [
                'key' => getenv('ORBIT_KEY'),
                'info' => $info,
            ])->throw()->json();

            $this->info('Data sent to Orbit successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to send data to Orbit.');
            Log::error($e->getMessage());
        }
    }
}
