<?php

namespace Halaei\Helpers\Crypt;

/**
 * Encrypt numbers into strings.
 *
 * This class does not generate cryptographically secure values,
 * and should not be used for cryptographic purposes.
 *
 * This class does not take care of integer overflow.
 */
class NumCrypt
{
    /**
     * @var string
     */
    private $charset;

    /**
     * @var int
     */
    private $key;

    /**
     * NumCrypt constructor.
     *
     * @param string $charset list of chars should be used for encoding.
     * @param int $key used for bitwise xor as a simple encryption algorithm.
     */
    public function __construct($charset = '0123456789abcdefghijklmnopqrstuvwxyz', $key = 308312529)
    {
        $this->charset = (string) $charset;
        $this->key = (int) $key;
    }

    /**
     * Convert a number to the string code of minumum length $padding.
     *
     * @param int $num
     * @param int $padding
     * @return string
     */
    public function encrypt($num, $padding = 6)
    {
        $num = $this->key ^ ((int) $num);
        $base = strlen($this->charset);
        $code = [];
        while ($num > 0) {
            $code[] = $num % $base;
            $num = (int) ($num / $base);
        }
        return $this->arrayCodeToString($code, $padding);
    }

    /**
     * Convert a az09 string code to a number.
     *
     * @param string $code
     *
     * @return int
     */
    public function decrypt($code)
    {
        $code = array_reverse(str_split(strtolower($code)));
        $base = strlen($this->charset);
        $num = 0;
        $pow = 1;
        foreach ($code as $c) {
            $num += $pow * $this->charToDigit($c);
            $pow *= $base;
        }
        return $this->key ^ (int) $num;
    }

    private function arrayCodeToString(array $code, $padding)
    {
        $str = '';
        foreach ($code as $digit) {
            $str = $this->digitToChar($digit) . $str;
        }

        return str_pad($str, $padding, $this->charset[0], STR_PAD_LEFT);
    }

    private function digitToChar($digit)
    {
        return $this->charset[$digit];
    }

    private function charToDigit($char)
    {
        return (int) strpos($this->charset, $char);
    }
}
