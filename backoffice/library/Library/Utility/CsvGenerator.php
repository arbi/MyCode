<?php

namespace Library\Utility;

/**
 * Class CsvGenerator
 * Utility class to generate and download csv files
 * @package Library\Utility
 *
 * @author Tigran Petrosyan
 * @todo Move this utility to FileManager module
 */
final class CsvGenerator {
	
	/**
	 * Generate CSV from any array
	 * @access
	 * 
	 * @param array $array
	 * @return string
	 */
	public function generateCsv(array $array)
    {
		ob_start();
		$fileStream = fopen("php://output", 'w');
		
		fputcsv($fileStream, array_keys(reset($array)));
		
		foreach ($array as $row) {
			fputcsv($fileStream, $row);
		}
		
		fclose($fileStream);
		
		return ob_get_clean();
	}
	
	/**
	 * Set right headers to given header object to download csv file
	 * 
	 * @param array $headerObject
	 * @param string $filename
	 */
	public function setDownloadHeaders($headerObject, $filename)
    {
		$now = gmdate("D, d M Y H:i:s");
		$headerObject->addHeaderLine('Expires', 'Tue, 03 Jul 2001 06:00:00 GMT');
		$headerObject->addHeaderLine('Cache-Control', 'max-age=0, no-cache, must-revalidate, proxy-revalidate');
		$headerObject->addHeaderLine('Last-Modified', $now . ' GMT');
		 
		// content type
		$headerObject->addHeaderLine('Content-Type', 'text/csv');
		 
		// force download
		$headerObject->addHeaderLine('Content-Type', 'application/force-download');
		$headerObject->addHeaderLine('Content-Type', 'application/octet-stream');
		$headerObject->addHeaderLine('Content-Type', 'application/download');
		 
		// disposition / encoding on response body
		$headerObject->addHeaderLine('Content-Disposition', "attachment; filename=\"" . $filename . "\"");
		$headerObject->addHeaderLine('Accept-Ranges', 'bytes');
		$headerObject->addHeaderLine('Content-Transfer-Encoding', 'binary');
	}
}

?>