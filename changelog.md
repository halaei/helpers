# Change Log

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
