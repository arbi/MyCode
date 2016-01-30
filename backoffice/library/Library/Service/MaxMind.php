<?php

namespace Library\Service;

use DDD\Dao\GeoliteCountry\GeoliteCountry;
use Zend\ServiceManager\ServiceManager;

/**
 * Service class to allow to import MaxMind GeoliteCountry database
 * @author Tigran Petrosyan
 */
final class MaxMind
{
	/**
	 * @access protected
	 * @var ServiceLocatorInterface
	 */
	protected $serviceLocator;

    /**
     * @param ServiceManager $serviceLocator
     */
    public function setServiceLocator(ServiceManager $serviceLocator)
    {
		$this->serviceLocator = $serviceLocator;
	}

    /**
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
		return $this->serviceLocator;
	}

    /**
     * @param $value
     * @return string
     */
    public function escapeString($value)
    {
		$search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
		$replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

        str_replace($search, $replace, $value);

		return $value;
	}

    /**
     * @return array|bool
     */
    public function applyGeoliteCountryCSV()
    {
		/** @var GeoliteCountry $geoliteDao */
		$filePath = '/ginosi/files/MaxMind/GeoliteCountry/geolite_country_latest.csv';

		$validationResult = $this->validateDatabaseFile($filePath);
		if ($validationResult !== true) {
			return 'Uploaded database file MIME type is invalid';
		}

		$geoliteDao = $this->getServiceLocator()->get('dao_geolite_country_geolite_country');

		$connection = $geoliteDao->adapter->getDriver()->getConnection();

		if (($handle = fopen($filePath, "r")) !== false) {
			$connection->beginTransaction();

			try {
				$geoliteDao->createGeoliteCountryTemporaryTable();

				$data = '';
				while (($row = fgetcsv($handle, 1000, ",")) !== false) {
					$dataRow = array(
						'ip_start' => "'" . $this->escapeString(str_replace('"','',$row[0])) . "'",
						'ip_end' => "'" . $this->escapeString(str_replace('"','',$row[1])) . "'",
						'ip_num_start' => $this->escapeString(str_replace('"','',$row[2])),
						'ip_num_end' => $this->escapeString(str_replace('"','',$row[3])),
						'iso' => "'" . $this->escapeString(str_replace('"','',$row[4])) . "'",
						'code' => "'" . $this->escapeString(str_replace('"','',$row[5])) . "'",
					);
					$dataRowString = implode(', ', $dataRow);
					$data .= '(' . $dataRowString . '),';
				}
				fclose($handle);

				$geoliteDao->insertNewData($data);

				$geoliteDao->replaceGeoliteCountryTableWithTemporaryTable();

				$connection->commit();

				return 'New database imported successfully!';
			} catch (\Exception $e){
				$connection->rollback();

				return 'Can\'t apply new Geolite Country database.' . $e->getMessage();
			}
		} else {
			return 'Error reading or opening file.';
		}
	}

    /**
     * @param $filePath
     * @return array|bool
     */
    private function validateDatabaseFile($filePath)
    {

		// check weather file exist or not
		if (!is_file($filePath)) {
			return array(
				'status' => 'error',
				'msg' => 'GeoIP database file doesn\'t exist.'
			);
		}

		// allowed mime types for csv
		$csvMimeTypes = array(
				'text/csv',
				'text/plain',
				'application/csv',
				'text/comma-separated-values',
				'application/excel',
				'application/vnd.ms-excel',
				'application/vnd.msexcel',
				'text/anytext',
				'application/octet-stream',
				'application/txt',
		);

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo, $filePath);

		// check mime type
		if (in_array($mime, $csvMimeTypes)) {
			finfo_close($finfo);
			return true;
		} else {
			return false;
		}
	}
}

?>
