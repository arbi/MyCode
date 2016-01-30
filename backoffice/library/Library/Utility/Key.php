<?php

/**
 * Door Key Codes
 *
 * @author tigran.tadevosyan
 */
namespace Library\Utility;

class Key
{
    public static function generateRandomKeyCode()
    {
		$doorCode = '';
		$existingDigits = array();

		for ($i=0; $i<4; $i++) {
			$nextDigit = rand(0, 9);
			while (in_array($nextDigit, $existingDigits)) {
				$nextDigit = rand(0, 9);
			}
			array_push($existingDigits, $nextDigit);
			$doorCode .= $nextDigit;
		}

        return $doorCode;
	}
}