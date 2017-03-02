<?php

namespace Halaei\Helpers\Eloquent;

/**
 * Detect the class of common SQLSTATE values.
 *
 * @link https://en.wikibooks.org/wiki/Structured_Query_Language/SQLSTATE
 */
class SqlState
{
    public static function is_integrity_constraint_violation($code)
    {
        return is_string($code) && preg_match('/^2300[0-1]$/', $code);
    }

    public static function is_transaction_rollback($code)
    {
        return is_string($code) && preg_match('/^4000[0-4]$/', $code);
    }
}
