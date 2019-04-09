<?php
namespace Pnet\Bus;


class Utils {

	/**
	 * converts a xmlString that is returned from the interface
	 * to an array.
	 * @param  string $xmlString xmlString from Interface
	 * @return array/false           array representation, false if empty, null if not parseable
	 */
	public static function xml2array($xmlString) {

		$xml = null;
		if(empty($xmlString)) {
			echo 'Given empty XML String to xml2array.';
			return false;
		}

		try {
			$use_errors = libxml_use_internal_errors(TRUE);
			//$xml = new \SimpleXMLElement($xmlString, LIBXML_NOERROR);
			$xml = new \SimpleXMLElement($xmlString);

			//echo '<pre>'.htmlspecialchars($xml->asXML()).'</pre>';
		} catch (\Exception $e) {

			echo 'Caught exception: ' . $e->getMessage() . chr(10);
			//echo libxml_get_last_error();
			echo 'Failed loading XML: ' . chr(10);
			echo '<pre>';
			print_r(htmlentities($xmlString));
			echo '</pre>';
			foreach(libxml_get_errors() as $error) {
				echo '- ' . $error->message . "\n";
			}
		}

		libxml_clear_errors();
		libxml_use_internal_errors($use_errors);


		return $xml;

		// TOdO - xml repsonse from soap geht ned gscheit decodieren ..
		$xmlString = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $xmlString);

		$xml = new \SimpleXMLElement($xmlString);
		return $xml;
	}

	/**
	 * creates an associated array combining header and row
	 * used when parsing SQLPayload
	 * @param  array &$row   array with data
	 * @param  integer $key    index of returned row, not used.
	 * @param  array $header array with keys, header row
	 * @return void
	 */
	protected static function combine_array(&$row, $key, $header) {
		if(is_array($row) && is_array($header) && count($row) == count($header)) {
			$row = array_combine($header, $row);
		} else {
			echo "ERROR while trying to combine_array: ";
			echo 'key: '. print_r($key, true) . "\n";
			echo 'row: '. print_r($row, true) . "\n";
			echo 'header: '. print_r($header, true) . "\n";
		}
	}

	/**
	 * cuts away first to lines, and creates and associated array with the actual data
	 * @param  string $payload payload data from the bus interfacce
	 * @return array         payload data as assoc array
	 */
	public static function parseSQLPayload($payload) {
		$payload = trim($payload);
		$payload = preg_replace("/(Row[0-9]*): (.*)/i", "$2", $payload);
		$lines = explode("\n", $payload);
		$lines = array_slice($lines, 2);

		// cutting first and last ' from each row in the list
		$lines = array_map(function($value) {
			return substr($value, 1, -1);
		}, $lines);
		// split after ','
		$lines = array_map(function($value) {
			return preg_split('/\',\'/', $value);
		}, $lines);

		// first entry are headers
		$header = array_shift($lines);

		// put headers as index for array
		array_walk($lines,'self::combine_array', $header);

		// return array
		$result = $lines;
		return $result;
	}

}
