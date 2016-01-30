<?php

namespace Library\Utility;

use DDD\Domain\User\User;
use Library\Constants\Constants;
use Library\Constants\WebSite;
use Library\Constants\DomainConstants;
use Zend\Crypt\Password\Bcrypt;
use Zend\Session\Container;

use Library\Finance\Base\Account;
use Zend\Stdlib\ArrayObject;

class Helper {

    public static function getSession($namespase = false)
    {
        $sessionContainer = Container::getDefaultManager();
        $sessionStorage   = $sessionContainer->getStorage();

        if ($namespase) {
            return $sessionStorage->$namespase;
        }

        return $sessionStorage;
    }

	/**
	 * @param $namespace
	 *
	 * @return Container|\ArrayObject
	 */
	public static function getSessionContainer($namespace)
    {
        $sessionContainer = new Container($namespace);

        return $sessionContainer;
    }

    public static function setFlashMessage(array $message, $namespace = 'use_zf2')
    {
        $flashSessionContainer = new Container($namespace);
        $flashSessionContainer->flash = $message;
    }

    public static function hashPassword($password)
    {
        return md5($password);
    }

    public static function bCryptPassword($password)
    {
        $bCrypt = new Bcrypt();

        return $bCrypt->create($password);
    }

    public static function bCryptVerifyPassword($password, $hash)
    {
        $bCrypt = new Bcrypt();

        return $bCrypt->verify($password, $hash);
    }

    /**
     * @param $userProfile
     * @param string $size
     * @param bool|false $exactFile
     * @return bool|string
     * @throws \Exception
     */
	public static function getUserAvatar($userProfile, $size = 'small', $exactFile = false)
    {
		if (!in_array($size, ['small', 'big'])) {
			return false;
		}

		$imgSize40  = 40;
		$imgSize150 = 150;
		$imgSuffix  = '';

        if ($userProfile instanceof \ArrayObject) {
            $avatarName = $userProfile['avatar'];
            $id         = $userProfile['id'];
        } elseif (is_object($userProfile) && $userProfile instanceof User) {
            $avatarName = $userProfile->getAvatar();
            $id         = $userProfile->getId();
        } else {
            throw new \Exception('UserProfile incorrect format');
        }


		if ($size == 'small') {
			$avatarName = str_replace('_' . $imgSize150, '_' . $imgSize40, $avatarName);
			$imgSuffix = $imgSize40;
		}

        // For mobile request
        if ($exactFile) {
            if (empty($avatarName) || !file_exists('/ginosi/images/profile/' . $id . '/' . $avatarName)) {
                return false;
            }
            return 'profile/' . $id . '/' . $avatarName;
        }

		if (empty($avatarName) || !file_exists('/ginosi/images/profile/' . $id . '/' . $avatarName)) {
			$avatarUrl = '//' . DomainConstants::BO_DOMAIN_NAME . Constants::VERSION . 'img/no' . $imgSuffix . '.gif';
		} else {
			$avatarUrl = '//' . DomainConstants::IMG_DOMAIN_NAME . '/profile/' . $id . '/' . $avatarName;
		}

		return $avatarUrl;
	}

    public static function deleteDirectory($dir)
    {
        if (!file_exists($dir) || !is_writable($dir)) {
            return true;
        }

        if (!is_dir($dir) || is_link($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!self::deleteDirectory($dir . "/" . $item)) {
                chmod($dir . "/" . $item, 0777);

                if (!self::deleteDirectory($dir . "/" . $item)) {
                    return false;
                }
            }
        }

        return rmdir($dir);
    }

    public static function urlForSite($string)
    {
        $slug = trim($string);
        $slug = preg_replace('/[^a-zA-Z0-9-\s\.]/', '', $slug);
        $slug = str_replace(' ', '-', $slug);
        $slug = strtolower($slug);

        return $slug;
	}

	public static function fixAmount($currency, $amount)
    {
		if ($amount == 0) {
			return $currency . $amount;
		}

		$sign = $amount < 0 ? '-': '';

		return $sign . $currency . abs($amount);
	}

    public static function getTranslateHash($value)
    {
        return md5($value.'gs');
    }

    public static function getCreditCardType($id)
	{
		$card = '';

		switch ($id) {
			case 1:
				$card = 'Visa';
			    break;
			case 2:
				$card = 'MasterCard';
			    break;
			case 3:
				$card = 'American Express';
			    break;
			case 4:
				$card = 'Discover';
                break;
			case 5:
				$card = 'JCB';
                break;
			case 6:
				$card = 'Diners Club';
                break;
		}

		return $card;
	}

    public static function getPenalty($penalty, $value, $cur)
    {
		$result = '';

		switch ($penalty) {
			case 1:
				$result = "{$value} % cancelation penalty will apply";
			    break;
			case 2:
				$result = "{$value} {$cur} cancelation penalty will apply";
			    break;
			case 3:
				$result = "$value night cancelation penalty will  apply";
			    break;
		}

		return $result;
	}

    /*
     *  checkbox -> ('checkbox', $new, $old, $object)
     *  comment  -> ('comment', $comment)
     *  comment  -> ('commentWithoutData', $comment)
     *  othe     -> ('othe', $value)
     */
    public static function setLog($type, $value, $old = false, $object = false)
    {
        if ($type == 'commentWithoutData' && ('' == $value)) {
            return '';
        }

        $authService = new \Zend\Authentication\AuthenticationService();
        $auth        = $authService->getIdentity();
        $logger      = $auth->firstname . ' ' . $auth->lastname;
        $timestamp   = date('Y-m-d H:i:s');
        $log         = '';

        if ($type == 'checkbox') {
            if ($value == $old) {
                return '';
            }

            $action = 1 == 	$old ? 'Checked' : 'Unchecked';
            $log = "|| {$object} || {$action} ----\n\n";
        } elseif ($type == 'comment') {
            $value = trim($value);

            if ('' == $value) {
                return '';
            }

            $timestamp = date('Y-m-d H:i:s');
            $log = "|| Comment ----\n{$value}\n\n";
        } elseif ($type == 'other') {
            $log = "|| $value ----\n\n";
        } elseif ($type == 'commentWithoutData') {
            return "\n\n{$value}\n\n";
        }

        return "---- {$timestamp} (Amsterdam Time) || {$logger} {$log}";

    }

    public static function stripTages($value)
    {
        return strip_tags(trim($value));
    }

    public static function commentParser($data)
    {
        $data = preg_replace("/Comment\s*----\s*\\\n\s*\\\n/U","Comment\n", $data);

        return preg_replace('/----(.*)----/U','<span class="commentBeautifier">$1</span>', $data);
    }

    public static function getDaysFromTwoDate($date1, $date2)
    {
        $dateIn     = new \DateTime($date1);
        $dateOut    = new \DateTime($date2);
        $interval   = $dateIn->diff($dateOut);

        return $interval->days;
    }

    public static function checkDatesByDaysCount($count, $date1, $date2 = false)
    {
        if (!$date2) {
            $date2 = date('Y-m-d');
        }

        $convertDate2 = new \DateTime($date2);
        $convertDate1     = new \DateTime($date1);

        $intervalAfterEntering  = $convertDate2->diff($convertDate1);

        if ((int)$intervalAfterEntering->format('%R%a') <= -$count) {
            return false;
        }

        return true;

    }

	public static function refactorDateRange($dateRange)
    {
		list($dateFrom, $dateTo) = explode(' - ', $dateRange);

		return [
			'date_from' => date('Y-m-d', strtotime($dateFrom)),
			'date_to' => date('Y-m-d', strtotime($dateTo)),
		];
	}

	/**
	 * @param array $weekDays
	 * @return string
	 */
	public static function reformatWeekdays(array $weekDays)
    {
		$output = [];

		foreach ($weekDays as $weekDay => $is) {
			if ($is) {
				$output[] = $weekDay;
			}
		}

		return implode(',', $output);
	}

    public static function getBlogShort($string, $max_length = 300, $split = true)
    {
	    $separator = '<read_more>';
	    $etc = '...';

        if (strpos($string, $separator) && $split) {
            $postHead = explode($separator, $string);

            return $postHead[0];
        } else {
            if (strlen($string) > $max_length) {
	            $stripedString = strip_tags($string);
	            $stripedString = substr($stripedString, 0, $max_length);

	            return $stripedString . $etc;
            }
        }

        return $string;
    }

    public static function clearBlogTag($string)
    {
        return str_replace(['<p><read_more></p>', '<read_more>'], '', $string);
    }

    public static function getLanguage()
    {
        $sessionNamespase = Helper::getSessionContainer('visitor');
        $language = $sessionNamespase->language;

        if (is_null($language)) {
            $language = 'en';
        }

        return $language;
    }

    public static function getCurrency()
    {
        $sessionNamespase = Helper::getSessionContainer('visitor');
        $currency = $sessionNamespase->currency;

        if (is_null($currency)) {
            $currency = WebSite::DEFAULT_CURRENCY;
        }

        return $currency;
    }

    public static function getImgUrl($original, $big = '500', $small = '70')
    {
        $smallImg = str_replace('orig', $small, $original);
        $bigImg = str_replace('orig', $big, $original);

        return ['small' => $smallImg, 'big' => $bigImg];
    }

    public static function getVideoUrl($original)
    {
        preg_match('/v=([^&#]+)/i', $original, $data);

        if (isset($data[1])) {
            return '//img.youtube.com/vi/' . $data[1] . '/0.jpg';
        }

        return false;
    }

    public static function urlForSearch($string, $isSlug = FALSE)
    {
        $slug = trim($string);

        if (!$isSlug) {
            $slug = str_replace('-',' ', $slug);
        }

        $slug = strtolower($slug);

        return $slug;
	}

    public static function dateForUrl($date)
    {
        return date('d-m-Y', strtotime($date));
    }

    public static function dateForSearch($date)
    {
        return date('d M Y', strtotime($date));
    }

    public static function imgGet($img, $size = 500)
    {
        $imgs       = self::getImgUrl($img, $size);
        $bigImg     = $imgs['big'];

        if (file_exists(Website::IMAGES_PATH.$bigImg)) {
            return '//' . DomainConstants::IMG_DOMAIN_NAME . $bigImg;
        }

        return false;
    }

    public static function getImgByWith($original, $size = false, $symlink = false, $secure = false)
    {
        if (!$size) {
            $img = $original;
        } else {
            $img = str_replace('orig', $size, $original);
        }

        if (file_exists(Website::IMAGES_PATH . $img)) {
            if ($symlink AND $secure) {
                return 'https://' . DomainConstants::WS_SECURE_DOMAIN_NAME . '/images' . $img;
            } elseif ($symlink) {
                return '//' . DomainConstants::WS_DOMAIN_NAME . '/images' . $img;
            } else {
                return '//' . DomainConstants::IMG_DOMAIN_NAME . $img;
            }
        }

        return false;
    }

    public static function getUserCountry()
    {
        $sessionNamespase = Helper::getSessionContainer('visitor');
        $country_name = $sessionNamespase->country_name;
        $country_id = $sessionNamespase->country_id;
        $country_iso = $sessionNamespase->country_iso;

        return [
            'country_name' => $country_name,
            'country_id' => $country_id,
            'country_iso' => $country_iso,
        ];
    }

    public static function isBackofficeUser()
    {
        if (isset($_COOKIE['backoffice_user']) && is_numeric($_COOKIE['backoffice_user']) && $_COOKIE['backoffice_user'] > 0) {
            return true;
        }

        return false;
    }

    public static function getDateByTimeZone($date, $timezone, $format = 'Y-m-d', $diffHours = '0')
    {
        if ($timezone === null) {
            return FALSE;
        }

        if ($diffHours) {
            $date .= ' ' . $diffHours . ' hour';
        }

        $adjustedDate = new \DateTime($date, new \DateTimeZone($timezone));

        return $adjustedDate->format($format);
    }

    /**
     * @todo change name
     */
    public static function getCurrenctDateByTimezone($timezone, $format = 'Y-m-d', $diffHours = '0')
    {
       return self::getDateByTimeZone('now', $timezone, $format, $diffHours);
    }

    public static function incrementDateByTimezone($timezone, $hours, $format = 'Y-m-d')
    {
        return self::getDateByTimeZone('now +' . $hours . 'hours', $timezone, $format);
    }

    public static function getDateListInRange($first, $last)
    {
        $arrayDate = [];
        $current = strtotime($first);
        $last = strtotime($last);

        while ($current <= $last) {
            $arrayDate[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        return $arrayDate;
    }

	public static function getTimezoneOffset($remoteTz, $originTz = null, $sign = false, $gmt = false)
    {
		if ($originTz === null) {
			if (!is_string($originTz = date_default_timezone_get())) {
				return false;
			}
		}

		$originDtz = new \DateTimeZone($originTz);
		$remoteDtz = new \DateTimeZone($remoteTz);
		$originDt = new \DateTime('now', $originDtz);
		$remoteDt = new \DateTime('now', $remoteDtz);

		$offset = $originDtz->getOffset($originDt) - $remoteDtz->getOffset($remoteDt);
		$offset /= 3600;

		if (is_int($offset)) {
			$offset = $offset . ':00';
		} else {
			list($whole, $fraction) = explode('.', $offset);

			$offset = $whole . ':' . ($fraction / 10) * 60;
		}

		if ($sign) {
			$offset = $offset > 0 ? '+' . $offset : $offset;
		}

		if ($gmt) {
			$offset = 'GMT ' . $offset;
		}

		return $offset;
	}

    /**
     * @param string $text
     * @param array $data
     * @return string
     */
    public static function evaluateTextline($text, $data = [])
    {
        if (empty($data)) {
            return $text;
        }

        $values = array_values($data);
        $keys   = array_keys($data);

        if (count($keys) != count($values)) {
            return $text;
        }

        return str_replace($keys, $values, $text);
    }

    public static function hashForFrontierCharge($bookingId)
    {
        return md5($bookingId . 'fch');
    }

	public static function getTeamAvatar()
    {
	    $teamAvatarUrl =
            '//' . DomainConstants::BO_DOMAIN_NAME .
            Constants::VERSION . 'img/teamwork.gif';

		return $teamAvatarUrl;
	}

    public static function encrypt($string, $key)
    {
        return base64_encode(
            mcrypt_encrypt(
                MCRYPT_RIJNDAEL_256,
                md5($key),
                $string,
                MCRYPT_MODE_CBC,
                md5(
                    md5($key)
                )
            )
        );
    }

    public static function decrypt($string, $key)
    {
        return rtrim(
            mcrypt_decrypt(
                MCRYPT_RIJNDAEL_256,
                md5($key),
                base64_decode($string),
                MCRYPT_MODE_CBC,
                md5(
                    md5($key)
                )
            ), "\0"
        );
    }

    public static function formatCreditCard($cc) {
        $cc_length = strlen($cc);
        $newCreditCard = substr($cc, -4);

        for ($i = $cc_length - 5; $i >= 0; $i--) {
            if ((($i + 1) - $cc_length) % 4 == 0) {
                $newCreditCard = ' ' . $newCreditCard;
            }

            $newCreditCard = $cc[$i] . $newCreditCard;
        }

        return $newCreditCard;
    }

    public static function changeDateFormat($date, $format = 'Y-m-d')
    {
        return date($format, strtotime($date));
    }

    /**
     * @param $date
     * @return string
     */
    public static function getDateWeekType($date)
    {
        $dateSeeker = new \DateTime($date);
        $weekDayName = $dateSeeker->format('D');
        return in_array($weekDayName, \DDD\Service\Apartment\Inventory::$weekEndDays) ? 'weekend_percent' : 'week_percent';
    }

    /**
     * @param $weekDay
     * @return int
     */
    public static function siftWeekDay($weekDay)
    {
        return $weekDay == 0 ? 6 : $weekDay - 1;
    }

    /**
     * @param int $bytes
     * @param int $decimals
     * @return string
     */
    public static function humanFilesize($bytes, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[(int)$factor] . ($factor ? $sz[0] : '');
    }

    /**
     * Draw thumbnail
     *
     * @param $image
     * @param $width
     * @param $height
     * @return bool|void
     */
    public static function thumbnail($image, $width, $height)
    {
        if (!is_readable($image)) {
            return false;
        }

        $imageProperties = getimagesize($image);
        $imageWidth = $imageProperties[0];
        $imageHeight = $imageProperties[1];

        if (!$imageHeight) {
            return false;
        }

        $imageRatio = $imageWidth / $imageHeight;
        $type = $imageProperties["mime"];

        if (!$width && !$height) {
            $width = $imageWidth;
            $height = $imageHeight;
        }

        if (!$width) {
            $width = round($height * $imageRatio);
        }

        if (!$height) {
            $height = round($width / $imageRatio);
        }

        if ($type == "image/jpeg") {
            header('Content-type: image/jpeg');
            $thumb = imagecreatefromjpeg($image);
        } elseif ($type == "image/png") {
            header('Content-type: image/png');
            $thumb = imagecreatefrompng($image);
        } else {
            return false;
        }

        $tempImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($tempImage, $thumb, 0, 0, 0, 0, $width, $height, $imageWidth, $imageHeight);
        $thumbnail = imagecreatetruecolor($width, $height);
        imagecopyresampled($thumbnail, $tempImage, 0, 0, 0, 0, $width, $height, $width, $height);

        if ($type == "image/jpeg") {
            imagejpeg($thumbnail);
        } else {
            imagepng($thumbnail);
        }

        imagedestroy($tempImage);
        imagedestroy($thumbnail);
    }

    /**
     * @param string $amount
     * @return string
     */
    public static function formatAmount($amount)
    {
//        return number_format($amount, 2, '.', '');
        return $amount;
    }

    public static function parsFormInvalidMessages($errors)
    {
        $messages = '';
        foreach ($errors as $key => $row) {
            if (!empty($row)) {
                $messages .= ucfirst($key) . ' ';
                $messages_sub = '';

                foreach ($row as $rower) {
                    $messages_sub .= $rower;
                }

                $messages .= $messages_sub . '<br>';
            }
        }
        return $messages;
    }

    public static function truncateNotBreakingHtmlTags($text, $length = 200, $ending = '...', $exact = false, $considerHtml = true)
    {
        if ($considerHtml) {
            // if the plain text is shorter than the maximum length, return the whole text
            if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            // splits all html-tags to scanable lines
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
            $total_length = strlen($ending);
            $open_tags = array();
            $truncate = '';
            foreach ($lines as $line_matchings) {
                // if there is any html-tag in this line, handle it and add it (uncounted) to the output
                if (!empty($line_matchings[1])) {
                    // if it's an "empty element" with or without xhtml-conform closing slash
                    if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                        // do nothing
                        // if tag is a closing tag
                    } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                        // delete tag from $open_tags list
                        $pos = array_search($tag_matchings[1], $open_tags);
                        if ($pos !== false) {
                            unset($open_tags[$pos]);
                        }
                        // if tag is an opening tag
                    } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                        // add tag to the beginning of $open_tags list
                        array_unshift($open_tags, strtolower($tag_matchings[1]));
                    }
                    // add html-tag to $truncate'd text
                    $truncate .= $line_matchings[1];
                }
                // calculate the length of the plain text part of the line; handle entities as one character
                $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
                if ($total_length + $content_length > $length) {
                    // the number of characters which are left
                    $left = $length - $total_length;
                    $entities_length = 0;
                    // search for html entities
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                        // calculate the real length of all entities in the legal range
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entities_length <= $left) {
                                $left--;
                                $entities_length += strlen($entity[0]);
                            } else {
                                // no more characters left
                                break;
                            }
                        }
                    }
                    $truncate .= substr($line_matchings[2], 0, $left + $entities_length);
                    // maximum lenght is reached, so get off the loop
                    break;
                } else {
                    $truncate .= $line_matchings[2];
                    $total_length += $content_length;
                }
                // if the maximum length is reached, get off the loop
                if ($total_length >= $length) {
                    break;
                }
            }
        } else {
            if (strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = substr($text, 0, $length - strlen($ending));
            }
        }
        // if the words shouldn't be cut in the middle...
        if (!$exact) {
            // ...search the last occurance of a space...
            $spacepos = strrpos($truncate, ' ');
            if (isset($spacepos)) {
                // ...and cut the text in this position
                $truncate = substr($truncate, 0, $spacepos);
            }
        }
        // add the defined ending to the text
        $truncate .= $ending;
        if ($considerHtml) {
            // close all unclosed html-tags
            foreach ($open_tags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }
        return $truncate;
    }

    /**
     * @param string $dateFrom
     * @param string|null $dateTo
     * @return string
     */
    public static function getReadableDifferenceBetweenDates($dateFrom, $dateTo = null)
    {
        if (is_null($dateTo)) {
            $dateTo = date('Y-m-d H:i:s');
        }

        $differenceDaysCount = self::getDaysFromTwoDate($dateFrom, $dateTo);

        if ($differenceDaysCount > 365) {
            $yersRound = floor($differenceDaysCount / 365);

            $result = $yersRound . (($yersRound > 1) ? ' years' : ' year');
        } elseif ($differenceDaysCount > 30) {
            $monthRound = floor($differenceDaysCount / 30);

            $result = $monthRound . (($monthRound > 1) ? ' months' : ' month');
        } else {
            $result = $differenceDaysCount . (($differenceDaysCount > 1) ? ' days' : ' day');
        }

        $result .= ' ago';

        return $result;
    }


    /**
     * Returns edit page path for transaction account entities
     * If type is not a valid transaction account type, will return "/"
     * @param $accountType
     * @param $accountId
     * @return string
     */
    public static function getEntityEditPath($accountType, $accountId)
    {
        $path = '/';
        // Currently this method handles only entities related to transfers
        // Because we don't have an entity type for all the entities we use
        // In our system
        switch ($accountType) {
            case Account::TYPE_MONEY_ACCOUNT:
                $path = '/finance/money-account/edit/' . $accountId;
                break;
            case Account::TYPE_PARTNER:
                $path = '/partner/edit/' . $accountId;
                break;
            case Account::TYPE_SUPPLIER:
                $path = '/finance/suppliers/edit/' . $accountId;
                break;
            case Account::TYPE_PEOPLE:
                $path = '/profile/' . $accountId;
                break;
        }

        return $path;
    }

    /**
     * @param $string
     * @param int $length
     * @param string $append
     * @return array|string
     */
    public static function cutStringAndAppend($string, $length=140, $append="&hellip;")
    {
        $string = trim($string);

        if (strlen($string) > $length) {
            $string = wordwrap($string, $length);
            $string = explode("\n", $string, 2);
            $string = $string[0] . $append;
        }

        return $string;
    }
}
