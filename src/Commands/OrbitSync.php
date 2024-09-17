<?php

namespace Trendyminds\Orbit\Commands;

use Facades\Statamic\Marketplace\Marketplace;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\Addon;
use Statamic\Facades\Stache;
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
        $data = [
            'url' => config('app.url'),
            'admin_url' => config('app.url').'/'.config('statamic.cp.route'),
            'php_version' => phpversion(),
            'composer_version' => $this->composer->getVersion() ?? 'N/A',
            'debug_mode' => config('app.debug'),
            'maintenance_mode' => $this->laravel->isDownForMaintenance(),
            'ray_enabled' => (bool) env('RAY_ENABLED'),
            'platform' => 'Laravel',
            'platform_version' => $this->laravel->version(),
            'cms' => 'Statamic',
            'cms_version' => Statamic::version().' '.(Statamic::pro() ? 'Pro' : 'Solo'),
            'static_caching' => config('statamic.static_caching.strategy') ? true : false,
            'stache_watcher' => Stache::isWatcherEnabled(),
            'addons' => Addon::all()->map(fn ($addon) => [
                'name' => $addon->name(),
                'package' => $addon->package(),
                'version' => $addon->version(),
                'latest_version' => $addon->latestVersion() && $addon->version() !== $addon->latestVersion()
                        ? $addon->latestVersion()
                        : null,
            ])->sortBy('name')
                ->prepend([
                    'name' => 'Statamic',
                    'package' => 'statamic/cms',
                    'version' => Statamic::version(),
                    'latest_version' => Marketplace::statamic()->changelog()->availableUpdatesCount() > 0
                        ? Marketplace::statamic()->changelog()->latest()->version
                        : null,
                ])
                ->values()->toArray(),
        ];

        try {
            Http::acceptJson()->post('https://orbit.trendyminds.com/api/transmit', [
                'key' => getenv('ORBIT_KEY'),
                ...$data,
            ])->throw()->json();

            $this->info('Data sent to Orbit successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to send data to Orbit');
            Log::error($e->getMessage());
        }
    }
}
