<?php

namespace Library\Validator;

use Zend\Validator\EmailAddress;
use Zend\I18n\Validator\Alnum;

class ClassicValidator {

    public static function validateEmailAddress($value)
    {
            $validatorEmail = new EmailAddress();
            return $validatorEmail->isValid($value);
    }

    public static function validateAlnum($value)
    {
            $validator = new Alnum();
            return $validator->isValid($value);
    }

    public static function getPopularRegex()
    {
        return '/^[0-9\p{L}\s\,\.\-\_\:]+$/';
    }

    public static function getVRRegex()
    {
        return '/^[0-9\p{L}\s\,\.\-\_\:\&\'\"]+$/';
    }

	public static function getDateRegex() {
		return '/^([0-9]{1,2}) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) ([0-9]{4})$/i';
	}

    public static function checkDateRegex($date, &$matches = [])
    {
        return (bool)preg_match(self::getDateRegex(), $date, $matches);
    }

	public static function isAmount($amount) {
		if (is_numeric($amount)) {
			return number_format($amount, 2, ',', ' ');
		}

		return false;
	}

	public static function checkDateRegexYMD($date, &$matches = []) {
		return (bool)preg_match('/^(\d{4})\-(\d{2})\-(\d{2})$/', $date, $matches);
	}

    public static function validateDate($date, $format = 'd-m-Y')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public static function checkApartmentTitle($title)
    {
        return (bool)preg_match('/^[0-9a-z\-]+$/i', $title);
    }

    public static function checkCityName($title)
    {
        return (bool)preg_match('/^[0-9a-z\-\s]+$/i', $title);
    }

    public static function validateAutocomplateSearch($value)
    {
            return (bool)preg_match('/^[0-9a-z\s]+$/i', $value);
    }

    public static function regexHolderName()
    {
        return '/^[0-9a-z\-\s\.\_\'\"]+$/i';
    }

    public static function regexPhone()
    {
        return '/^\d{8,}$/';
    }

    public static function checkNewsTitle($title)
    {
        return (bool)preg_match('/^[0-9a-z\-\.]+$/i', $title);
    }

    public static function checkCCPdateCode($code)
    {
        return (bool)preg_match('/^[0-9a-z*]+$/i', $code);
    }

    public static function checkScriptTags($strings)
    {
        foreach ($strings as $string) {
            $string = preg_replace('/\s+/', '', $string);
            if (preg_match('/[\&lt\;|<]script/', $string)) {
                return false;
            }
        }
        return true;

    }
}
