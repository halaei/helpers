# Change Log

## v0.3.1

- Use Laravel 5.4 new feature `DB::rollBack(0)` when refreshing database connection.

## v0.3.0

- Let RedisLock directly work with predis instead of `illuminate/redis`.
- Remove `Halaei\Cache` in favor of `illuminate\cache` version 5.4.

## v0.2.2

- New feature: NumCrypt to encrypt/decrypt numbers into random-looking strings.

## v0.2.1

- New feature: RedisLock
