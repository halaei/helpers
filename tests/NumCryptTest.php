<?php

namespace HalaeiTests;

use Halaei\Helpers\Crypt\NumCrypt;
use PHPUnit_Framework_TestCase;

class NumCryptTest extends PHPUnit_Framework_TestCase
{
    public function test_some_values()
    {
        $crypt = new NumCrypt();
        $this->assertSame('53k7hx', $crypt->encrypt(36));

        $this->assertSame('5w2mpa', $crypt->encrypt(123423423));
        $this->assertSame(123423423, $crypt->decrypt('5W2MPA'));
    }

    public function test_num_to_code_with_default_constructor()
    {
        $crypt = new NumCrypt();
        for ($i = 0; $i < 1000000; $i++) {
            $code = $crypt->encrypt($i);
            $this->assertRegExp('/^[0-9a-z]{6}$/', $code);
            $this->assertSame($i, $crypt->decrypt($code));
        }
    }

    public function test_some_values_with_custom_constructor()
    {
        $crypt = new NumCrypt('9876543210abcdef', 0);
        $this->assertSame(16, $crypt->decrypt('999989'));
        $this->assertSame('89', $crypt->encrypt(16, 0));
    }


    public function test_num_to_code_with_custom_constructor()
    {
        $crypt = new NumCrypt('9876543210abcdef', 19);
        for ($i = 1000000; $i < 1200000; $i++) {
            $code = $crypt->encrypt($i);
            $this->assertRegExp('/^[0-9a-f]{6}$/', $code);
            $this->assertSame($i, $crypt->decrypt($code));
        }
    }
}
