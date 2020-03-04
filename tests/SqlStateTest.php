<?php

namespace HalaeiTests;

use Halaei\Helpers\Eloquent\SqlState;
use PHPUnit\Framework\TestCase;

class SqlStateTest extends TestCase
{
    public function test_integrity_constraint_violation()
    {
        $this->assertTrue(SqlState::is_integrity_constraint_violation('23000'));
        $this->assertTrue(SqlState::is_integrity_constraint_violation('23001'));
        $this->assertFalse(SqlState::is_integrity_constraint_violation(23001));
        $this->assertFalse(SqlState::is_integrity_constraint_violation('40001'));
    }
    public function test_deadlock()
    {
        $this->assertTrue(SqlState::is_transaction_rollback('40000'));
        $this->assertTrue(SqlState::is_transaction_rollback('40001'));
        $this->assertTrue(SqlState::is_transaction_rollback('40002'));
        $this->assertTrue(SqlState::is_transaction_rollback('40003'));
        $this->assertTrue(SqlState::is_transaction_rollback('40004'));
        $this->assertFalse(SqlState::is_transaction_rollback(40001));
        $this->assertFalse(SqlState::is_transaction_rollback('23000'));
    }
}
