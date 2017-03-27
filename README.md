# Miscellaneous Helpers for PHP and Laravel

[![Build Status](https://travis-ci.org/halaei/helpers.svg)](https://travis-ci.org/halaei/helpers)
[![Latest Stable Version](https://poser.pugx.org/halaei/helpers/v/stable)](https://packagist.org/packages/halaei/helpers)
[![Total Downloads](https://poser.pugx.org/halaei/helpers/downloads)](https://packagist.org/packages/halaei/helpers)
[![Latest Unstable Version](https://poser.pugx.org/halaei/helpers/v/unstable)](https://packagist.org/packages/halaei/helpers)
[![License](https://poser.pugx.org/halaei/helpers/license)](https://packagist.org/packages/halaei/helpers)

## About this Package
This is a personal package of small pieces of codes that I have needed in one specific project once, and they may be needed in other projects later on, too.
So this is a way for me to stop copy and paste.

### Supervisor
Supervisor helps you with running a command in an loop and monitor it. The code is heavily based on the laravel daemon queue implementation.
- Supervisor prevents the command from consuming too much memory, getting frozen or taking too long.
- Supervisor gracefully stops the loop of executing the given command if:
    - `artisan queue:restart` command is issued,
    - or soft memory limit is reached,
    - or ttl is reached,
    - or too many consecutive runs of the command where failed,
    - or `SIGTERM` signal is received.
- Supervisor pauses the loop when:
    - `artisan down` command is issued,
    - or`SIGUSR2` signal is received.

```php
use Halaei\Helpers\Supervisor\Supervisor;

app(Supervisor::class)->supervise(GetTelegramUpdates::class);

class GetTelegramUpdates
{
    function handle(Api $api)
    {
        $updates = $api->getUpdates(...);
        $this->queueUpdates($updates);
    }
    function queueUpdates($updates)
    {
        ...
    }
}
```

To configure the behaviour of supervisor, you can pass an instance of `Halaei\Helpers\Supervisor\SupervisorOptions` as the second argument to the `supervise()` method.

### Eloquent Batch Update & Insert Ignore
Sometimes I face situations when it is important to update multiple rows of a table at once, and performance does matter,
especially in terms of the number of queries.
Here is an attempt to generally solve the problem using CASE WHEN THEN statements:

```php
$newSensorData = [
    12 => ['value' => 32, 'observed_at' => '2016-06-30 12:30:01'],
    13 => ['value' => 33, 'observed_at' => '2016-06-30 12:30:05'],
    16 => ['value' => 30, 'observed_at' => '2016-06-30 12:30:05'],
];
$sensors = Sensor::whereIn('id', array_keys($newSensorData))->get();
foreach($sensors as $sensor) {
    //some calculations then save the new values
    $sensor->value = $newSensorData[$sensor->id]['value'];
    $sensor->observed_at = $newSensorData[$sensor->id]['observed_at'];
}
$sensors->update();
```

The resulting SQL statement (assuming that the old value for sensor#12 is 32):

```sql
UPDATE `sensors` SET
`value` = CASE 
    WHEN `id` = 13 THEN 33
    WHEN `id` = 16 THEN 30
ELSE `value` END,
`observed_at` = CASE
    WHEN `id` = 12 THEN  '2016-06-30 12:30:01'
    WHEN `id` = 13 THEN '2016-06-30 12:30:05'
    WHEN `id` = 16 THEN '2016-06-30 12:30:05'
ELSE `observed_at` END
WHERE id in (12, 13, 16)
```

To enable batch update feature (+ insert ignore) register `\Halaei\Helpers\Eloquent\EloquentServiceProvider::class` in the list of providers in `config/app.php`.

### Disabling blade @parent
@parent feature of Laravel framework is implemented with a [minor security bug](https://github.com/laravel/framework/issues/10068).
So you may need to disable the feature. If in your `config/app.php` file relplace `\Illuminate\View\ViewServiceProvider::class` with `\Halaei\Helpers\View\ViewServiceProvider::class`.

### Redis-Based mutual exclusive lock
The `\Halaei\Helpers\Redis\Lock` class provides Redis-Based mutual exclusive locks with auto-release timer. The implementation is based on `rpoplpush` and `brpoplpush` Redis commands.

A process that requires a lock should call the `lock($name, $tr = 2)` method in a loop until it returns true;
where `$name` is the name of the lock and `$tr` is the auto-release timer in seconds (with milliseconds resolution).
If the lock is on-hold, the `lock` method waits for at most `$tr` seconds, until the lock is released.

When a process is done it must immediately release the lock by calling the `unlock($name)` method, with the same `$name` parameter used for calling the `lock` method.
If the process holds a lock for more that `$tr` seconds, it will be regarded as a failed process and the lock will be automatically released.

Note: Callers to the `lock` may not be aware of the release of the lock, if the lock was auto-released after the call to the `lock` method. So they should call `lock` once more.

```php
use \Halaei\Helpers\Redis\Lock;

$lock = app(Lock::class);

...
// 1. Wait for lock named 'critical_section'
while (! $lock->lock('critical_section', 0.1) {}
// 2. Do some critical job
//...
// 3. Release the lock
$lock->unlock('critical_section', 0.1);
```

### Clean-up DB transactions between handling queued jobs
In order to make sure there is nothing wrong with the default DB connection even after a messed-up handling of a queued job,
call `Halaei\Helpers\Listeners\RefreshDBConnections::boot()` in `AppServiceProvider::boot()` function:


### Number Encryption

If you need to obfuscate auto-increment numbers into random-looking strings, use the `Numcrypt` class:

```php
use Halaei\Helpers\Crypt\NumCrypt;

$crypt = new NumCrypt();
echo $crypt->encrypt(36); // 53k7hx
echo $crypt->decrypt('53k7hx'); // 36
```

The NumCrypt constructor accepts charset for the accepted characters in the output and a key for encryption (uxing XOR).
By default the charset is `[a-z0-9]`, and the key is 308312529.

**Note**: NumCrypt is not meant to be cryptographically secure.

```php
use Halaei\Helpers\Crypt\NumCrypt;

$crypt = new NumCrypt('9876543210abcdef', 0 /*dont XOR*/);
echo $crypt->encrypt(16, 0 /*no padding*/); // 89
echo $crypt->decrypt('999989'); // 36
```

## License
This package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
