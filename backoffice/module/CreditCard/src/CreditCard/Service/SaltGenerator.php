<?php

namespace CreditCard\Service;

use DDD\Service\ServiceBase;

/**
 * Class SaltGenerator
 * @package CreditCard\Service
 *
 * @author Tigran Petrosyan
 */
class SaltGenerator extends ServiceBase
{
    const SALT_LENGTH = 10;

    /**
     * @var string
     */
    private $alphabet;

    /**
     * @var int
     */
    private $alphabetLength;

    /**
     * @param string $alphabet
     */
    public function __construct($alphabet = '')
    {
        if ('' !== $alphabet) {
            $this->setAlphabet($alphabet);
        } else {
            $this->setAlphabet(
                implode(range('a', 'z'))
                . implode(range('A', 'Z'))
                . implode(range(0, 9))
            );
        }
    }

    /**
     * @return string
     */
    public function generateSalt()
    {
        return $this->generateToken(self::SALT_LENGTH);
    }


    /**
     * @param $length
     * @return string
     */
    private function generateToken($length)
    {
        $token = $this->generate($length);

        return $token;
    }

    /**
     * @param string $alphabet
     */
    private function setAlphabet($alphabet)
    {
        $this->alphabet = $alphabet;
        $this->alphabetLength = strlen($alphabet);
    }

    /**
     * @param int $length
     * @return string
     */
    private function generate($length)
    {
        $token = '';

        for ($i = 0; $i < $length; $i++) {
            $randomKey = $this->getRandomInteger(0, $this->alphabetLength);
            $token .= $this->alphabet[$randomKey];
        }

        return $token;
    }

    /**
     * @param int $min
     * @param int $max
     * @return int
     */
    private function getRandomInteger($min, $max)
    {
        $range = ($max - $min);

        if ($range < 0) {
            // Not so random...
            return $min;
        }

        $log = log($range, 2);

        // Length in bytes.
        $bytes = (int) ($log / 8) + 1;

        // Length in bits.
        $bits = (int) $log + 1;

        // Set all lower bits to 1.
        $filter = (int) (1 << $bits) - 1;

        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));

            // Discard irrelevant bits.
            $rnd = $rnd & $filter;

        } while ($rnd >= $range);

        return ($min + $rnd);
    }
}
