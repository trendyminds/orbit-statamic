# Orbit Statamic

An Orbit adapter for Statamic

## Installation

1. Install package `trendyminds/orbit-statamic`
2. Add your site key to your `.env` as `ORBIT_KEY=my_site_key`
3. Setup a scheduled task within `app/Console/Kernel.php` ...

```php
$schedule->command('orbit:sync')->hourly();
```
