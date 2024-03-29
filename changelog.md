# Change Log

#v1.0.0
- Support resource as process input.
- Improving db:restore-dump: --force option and switching to Halaei\Helpers\Process.

#v0.9.1
- Artisan command 'db:log-slow-queries'.
- Artisan command 'db:backup-table'.
- Artisan command 'db:restore-dump'.
- Bug fixes in reading input from process.

#v0.9
- Minimum Laravel version: 8.
- Fix getOriginal() for Laravel >= 7.

#v0.8.0
- Drop support for old PHP and Laravel versions.
- Fix reporting throwable by calling `report()` helper function.

#v0.7.0
- Supervisor can return instead of exit using dontDie option.
- Bugfix in handling Throwable errors.

#v0.6.1
- New feature: Process.

#v0.6.0
- Laravel 6 & 7 support
- Drop PHP 7.0 support
- Drop Laravel 5.6 support

#v0.5.0
- Laravel 5.8 compatibility
- Supervisor can optionally stop on error.
- Supervisor sleeps one second on error to make it CPU friendly.
- Helper methods for Redis Lock: block() and instance().

#v0.4.7
- New feature: QuitsOnSignals trait.

#v0.4.6
- New feature: HasCastables trait.

#v0.4.5

- New feature: Random worker terminator.
- New feature: DataCollection::unionBy().

#v0.4.4

- New feature: fusing DataObjects and DataCollections.

#v0.4.2

- New feature: DataObject.

#v0.4.1

- New feature: Eloquent Cache.

#v0.4.0

- Make batchUpdate() compatible with Laravel 5.3+

## v0.3.3

- New feature: Supervisor

## v0.3.2

- New class `SqlState` to detect the cause of common database errors based on their SQLSTATE.

## v0.3.1

- Use Laravel 5.4 new feature `DB::rollBack(0)` when refreshing database connection.

## v0.3.0

- Let RedisLock directly work with predis instead of `illuminate/redis`.
- Remove `Halaei\Cache` in favor of `illuminate\cache` version 5.4.

## v0.2.2

- New feature: NumCrypt to encrypt/decrypt numbers into random-looking strings.

## v0.2.1

- New feature: RedisLock
