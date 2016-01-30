<?php

namespace Library\Utility;

use Zend\Db\Adapter\Driver\Pdo\Result;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\Session\Container;

class Debug {
	private static $starttime;

    public static function dump($var, $exit = true) {
        $isCli = self::isCommandLineInterface();

	    if ($var instanceof ResultSet || $var instanceof Result) {
		    foreach ($var as $varvar) {
			    echo ($isCli ? '' :  '<pre>');

                print_r($varvar);

                echo ($isCli ? '' :  '</pre>');
		    }
	    } elseif (is_array($var)) {
            echo ($isCli ? '' :  '<pre>');

		    print_r($var);

            echo ($isCli ? '' :  '</pre>');
	    } else {
            echo ($isCli ? '' :  '<pre>');

            var_dump($var);

            echo ($isCli ? '' :  '</pre>');
        }

	    if ($exit) {
		    exit;
	    }
    }

    public static function isCommandLineInterface() {
        return (php_sapi_name() === 'cli');
    }

	public static function htmlspecialchars($data) {
		echo htmlspecialchars($data); exit;
	}

	public static function htmlspecialcharsDump($data) {
		echo '<pre>';
		echo htmlspecialchars($data);
		echo '</pre>'; exit;
	}

	public static function toXML($data) {
		header('Content-Type: text/xml');
		echo $data; exit;
	}

	public static function timeExecStart() {
		$mtime = microtime();
		$mtime = explode(' ',$mtime);
		$mtime = $mtime[1] + $mtime[0];
		self::$starttime = $mtime;
	}

	public static function timeExecEnd() {
		$mtime = microtime();
		$mtime = explode(' ', $mtime);
		$mtime = $mtime[1] + $mtime[0];
		$endtime = $mtime;
		$totaltime = ($endtime - self::$starttime);

		echo "<code>This page was created in " . $totaltime . " seconds</code>";
	}

    /**
     * @param Select $select
     * @param PlatformInterface $platform
     */
    public static function dumpSQL($select, $platform = null) {
        die(
            str_replace('"', '', $select->getSqlString($platform))
        );
	}

	public static function dumpOneline($data, $exit = true, $return = false) {
		$output = [];

		if (count($data)) {
			foreach ($data as $prop => $val) {
				$output[] = $prop . ':' . $val;
			}
		}

		$output = '[' . implode(', ', $output) . ']';

		if ($return) {
			return $output;
		} else {
			echo $output;
		}

		if ($exit) {
			exit;
		}
	}
}

