<?php
namespace Pnet\Bus;

class Curl
{
	/**
	 * Fetches a page by sending the configured headers.
	 * Qnipp AP - rebuilding getUrl for Solr to curl
	 * with normal file_get_contents it's not possible to follow schema less redirects
	 *
	 * @param string $url
	 * @param mixed $post
	 * @param string[] $headers
	 * @param float $timeout
	 * @return string
	 */
	public static function send($url, $post = null, $headers = null, $timeout = 30, $checkSSL = false)
	{
		global $http_response_header;

		$options = [
			#CURLOPT_VERBOSE => 1,
			#CURLOPT_FRESH_CONNECT => 1,
			#CURLOPT_FORBID_REUSE => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_TIMEOUT => $timeout,
			CURLOPT_HEADER => 1,
			#CURLOPT_HTTPHEADER => $headers,
			CURLOPT_URL => $url,
			CURLINFO_HEADER_OUT => true,
		];

		if(!empty($headers)) {
			$options[ CURLOPT_HTTPHEADER ] = $headers;
		}

		if(!empty($post)) {

			$options[ CURLOPT_POST ] = 1;
			$options[ CURLOPT_CUSTOMREQUEST ] = "POST";

			if(is_array($post)) {
				$options[ CURLOPT_POSTFIELDS ] = http_build_query($post);
			} else {
				$options[ CURLOPT_POSTFIELDS ] = $post;
			}
		}


		// we do not need proxy for local calls!
		/*
		if(empty($checkSSL)) {
			$options[ CURLOPT_PROXY ] = "{$GLOBALS['CONFIG']['http_proxy']}:{$GLOBALS['CONFIG']['http_proxyport']}";
			//$options[ CURLOPT_PROXYTYPE ] = CURLPROXY_SOCKS5;
			$options[CURLOPT_PROXYTYPE] = CURLPROXY_SOCKS5_HOSTNAME;
			//$options[ CURLOPT_PROXYUSERPWD ] = 0;
		}
		*/

		if(empty($checkSSL)) {
			$options[ CURLOPT_SSL_VERIFYHOST ] = 0;
			$options[ CURLOPT_SSL_VERIFYPEER ] = 0;
		}

		$ch = curl_init();
		curl_setopt_array($ch, $options);

		$response = curl_exec($ch);
		$error = curl_error($ch);

		if ( $error != "" ) {
			$result['curl_error'] = $error;
			$rawResponse = false;
		} else {
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

			$result['header'] = substr($response, 0, $header_size);
			$result['body'] = substr( $response, $header_size );
			$result['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$result['last_url'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

			if($result['http_code'] != 200) {
				$complete_curl = curl_getinfo($ch);

				echo 'request: ';
				var_dump($complete_curl);

				echo 'options: ';
				var_dump($options);

				echo 'response: ';
				var_dump(htmlentities($response));
			}

			// trying to fill in for file_get_contents
			$http_response_header = $result['header'];
			$rawResponse = $result['body'];
		}
		curl_close($ch);

		return $result;
		//return $rawResponse;
	}
}
