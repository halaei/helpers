<?php

namespace HalaeiTests;

use Halaei\Helpers\Crypt\NumCrypt;
use PHPUnit\Framework\TestCase;

class NumCryptTest extends TestCase
{
    public function test_some_values()
    {
        $crypt = new NumCrypt();
        $this->assertSame('53k7hx', $crypt->encrypt(36));

        $this->assertSame('5w2mpa', $crypt->encrypt(123423423));
        $this->assertSame(123423423, $crypt->decrypt('05w2mpa'));
    }

    public function test_num_to_code_with_default_constructor()
    {
        $crypt = new NumCrypt();
        for ($i = 0; $i < 100000; $i++) {
            $code = $crypt->encrypt($i);
            $this->assertMatchesRegularExpression('/^[0-9a-z]{6}$/', $code);
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
        for ($i = 100000; $i < 120000; $i++) {
            $code = $crypt->encrypt($i);
            $this->assertMatchesRegularExpression('/^[0-9a-f]{6}$/', $code);
            $this->assertSame($i, $crypt->decrypt($code));
        }
    }

    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void
    {
        if (method_exists(parent::class, 'assertMatchesRegularExpression')) {
            parent::assertMatchesRegularExpression($pattern, $string, $message);
        } else {
            parent::assertRegExp($pattern, $string, $message);
        }
    }
}
