<?php
namespace Pnet\Bus;

use Pnet\Bus\Curl;
use Pnet\Bus\Utils;
use Pnet\Bus\Render;
use Pnet\Bus\BusElement;
use Pnet\Bus\BusStatus;

class Communicate {
	protected $sessionid = null;
	protected $host, $user, $password;

	protected $group_id = null;
	protected $user_id = null;

	/**
	 * @return null
	 */
	public function getUserId() {
		return $this->user_id;
	}

	/**
	 * @param null $user_id
	 */
	public function setUserId($user_id): void {
		$this->user_id = $user_id;
	}

	protected $elements = array();

	/**
	 * [__construct description]
	 * @param string $host     host or IP address including port of the bus webserver, scheme is always https
	 * @param string $user     username to login to the bus server
	 * @param string $password password used to login
	 * @param string $sessionid if available a sessionId can be set as well
	 */
	function __construct($host, $user, $password, $sessionid = null) {
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;

		if($sessionid) {
			$this->sessionid = $sessionid;
		}

	}

	public function getSessionId() {
		return $this->sessionid;
	}
	public function setSessionId($sessionid) {
		$this->sessionid = $sessionid;
	}

	public function getElements() {
		return $this->elements;
	}
	public function getElement($id) {
		return $this->elements[$id];
	}

	/**
	 * Login to the bus Webserver
	 * @return void
	 * @throws Exception if login was not successfully
	 */
	public function login() {
		$loginurl_t = 'https://%s/vimarbyweb/modules/system/user_login.php?sessionid=&username=%s&password=%s&remember=0&op=login';
		$loginurl = sprintf($loginurl_t, $this->host, $this->user, $this->password);
		$result = Curl::send($loginurl);
		if(!empty($result['curl_error'])) {
			throw new \Exception("Curl error during login: ". $result['curl_error'], 2017060723121);
		} else {
			$xml = Utils::xml2array($result['body']);
			if(!empty($xml->message)) {
				throw new \Exception("API error during login: ". $xml->message, 2017060723175);
			}
			if(empty($xml->sessionid)) {
				throw new \Exception("Could not get a SessionID after login.", 2017062923284);
			}
			// now we do have a session
			$this->sessionid = $xml->sessionid;
		}
	}

	/**
	 * reads out userdata (id, group and name) for a given name
	 * instance user_id, group_id and name are set within method call
	 * @return array sql result from login as array
	 */
	public function getUser() {
		// passwort mit: MSP
		$select_t = 'SELECT D_O.ID AS USER_ID, D_O.NAME AS USER_NAME, D_O.DESCRIPTION AS USER_DESCRIPTION,D_O.TYPE AS USER_TYPE, D_O.OPTIONALP AS OPTIONALP,D_OR.PARENTOBJ_ID AS USERGROUP_ID FROM DPADD_OBJECT AS D_O INNER JOIN DPADD_OBJECT_RELATION AS D_OR ON (D_O.ID=D_OR.CHILDOBJ_ID) WHERE (D_O.TYPE=&apos;USER&apos; AND D_O.NAME=&apos;%s&apos; AND D_OR.RELATION_WEB_TIPOLOGY=&apos;USERGROUP_RELATION&apos;) ORDER BY USER_NAME;';
		$select = sprintf($select_t, $this->user);

		$result = $this->querySQL($select);
		// only first line
		$result = $result[0];


		// set default user group
		$this->group_id = $result['USERGROUP_ID'];
		// set user_id
		$this->user_id = $result['USER_ID'];
		// reset username
		$this->username = $result['USER_NAME'];

		return $result;
	}

	/**
	 * send a request including payload to the webserver
	 * @param  array $post the payload as xml
	 * @return array       curl result as array, check for $result['body'] for content
	 * @throws \Exception if curl request was faulty
	 */
	protected function sendToWebserver($post) {
		$url_t = 'https://%s/cgi-bin/dpadws';
		$url = sprintf($url_t, $this->host);

		//$headers = 'SOAPAction:dbSoapRequest';
		$headers = [
			'SOAPAction' => 'dbSoapRequest',
			'SOAPServer' => '',
			#'X-Requested-With' => 'XMLHttpRequest',
			'Content-Type' => 'text/xml; charset="UTF-8"',
			// needs to be set to overcome: 'Expect' => '100-continue' header
			// otherwise header and payload is send in two requests if payload is bigger then 1024byte
			'Expect' => '',
		];
		$headers_tmp = [];
		foreach ($headers as $key => $value) {
			$headers_tmp[] = "$key: $value";
		}
		$headers = $headers_tmp;

		$result = Curl::send($url, $post, $headers);

		if(!empty($result['curl_error'])) {
			throw new \Exception("Curl error in QuerySQL: ". $result['curl_error'], 2017060800253);
		} else {
			return $result;
		}
	}

	/**
	 * sets a value for a given bus object
	 * @param integer $objectid id of bus object
	 * @param string $value    value to be set, 1/0 for switches, 0-100 for dimmer, etc
	 * @throws \Exception
	 * @return array
	 */
	public function setValue($objectid, $value) {
		$post_t = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Body><service-runonelement xmlns="urn:xmethods-dpadws"><payload>%d</payload><hashcode>NO-HASHCODE</hashcode><optionals>NO-OPTIONALS</optionals><callsource>WEB-DOMUSPAD_SOAP</callsource><sessionid>%s</sessionid><waittime>10</waittime><idobject>%d</idobject><operation>SETVALUE</operation></service-runonelement></soapenv:Body></soapenv:Envelope>';

		$post = sprintf($post_t, $value, $this->sessionid, $objectid);

		$result = $this->sendToWebserver($post);

		$xml = Utils::xml2array($result['body']);
		if($xml !== false) {
			$payload = $xml->xpath('//payload')[0];
			$query_result = Utils::parseSQLPayload($payload);
		} else {
			echo "\n".'Query Result empty for: '.  $post ."\n";
			echo "\n Length: ". strlen($post) ."\n";
			echo "\n post: ". htmlentities($post) . "\n";
			echo '>- '. print_r(htmlentities($result['body']), true) .'-<';
			$query_result = null;
		}

		return $query_result;
	}

	/**
	 * executes a SQL Query on the bus webserver, parses the xml results
	 * and returns an associative array
	 * @param string $select SQL Query
	 * @return array        SQL result, null if faulty
	 */
	public function querySQL($select) {

		$post_t = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Body><service-databasesocketoperation xmlns="urn:xmethods-dpadws"><payload>NO-PAYLOAD</payload><hashcode>NO-HASCHODE</hashcode><optionals>NO-OPTIONAL</optionals><callsource>WEB-DOMUSPAD_SOAP</callsource><sessionid>%s</sessionid><waittime>5</waittime><function>DML-SQL</function><type>SELECT</type><statement>%s</statement><statement-len>%d</statement-len></service-databasesocketoperation></soapenv:Body></soapenv:Envelope>';

		// to update element (send command to shades)
		// $post_t = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Body><service-runonelement xmlns="urn:xmethods-dpadws"><payload>0</payload><hashcode>NO-HASHCODE</hashcode><optionals>NO-OPTIONALS</optionals><callsource>WEB-DOMUSPAD_SOAP</callsource><sessionid>%s</sessionid><waittime>10</waittime><idobject>730</idobject><operation>SETVALUE</operation></service-runonelement></soapenv:Body></soapenv:Envelope>
		// start/stop dimmer
		// <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Body><service-runonelement xmlns="urn:xmethods-dpadws"><payload>1</payload><hashcode>NO-HASHCODE</hashcode><optionals>NO-OPTIONALS</optionals><callsource>WEB-DOMUSPAD_SOAP</callsource><sessionid>5b8bd7b958093</sessionid><waittime>10</waittime><idobject>710</idobject><operation>SETVALUE</operation></service-runonelement></soapenv:Body></soapenv:Envelope>

		// <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Body><service-runonelement xmlns="urn:xmethods-dpadws"><payload>0</payload><hashcode>NO-HASHCODE</hashcode><optionals>NO-OPTIONALS</optionals><callsource>WEB-DOMUSPAD_SOAP</callsource><sessionid>5b8bd7b958093</sessionid><waittime>10</waittime><idobject>710</idobject><operation>SETVALUE</operation></service-runonelement></soapenv:Body></soapenv:Envelope>


		$select = trim(str_replace("\n", " ", str_replace(array("'", '"'), '&apos;', $select) ));
		$post = sprintf($post_t, $this->sessionid, $select, strlen($select));

		$result = $this->sendToWebserver($post);

		$xml = Utils::xml2array($result['body']);
		if($xml !== false) {
			$payload = $xml->xpath('//payload')[0];
			$query_result = Utils::parseSQLPayload($payload);
		} else {
			echo "\n".'Query Result empty for: '.  $select ."\n";
			echo "\n Length: ". strlen($select) ."\n";
			echo "\n post: ". htmlentities($post) . "\n";
			echo '>- '. print_r(htmlentities($result['body']), true) .'-<';
			$query_result = null;
		}

		return $query_result;
			/*
			if(!empty($xml->message)) {
				throw new \Exception("API error during login: ". $xml->message, 2017060723175);
			}
			$this->sessionid = $xml->sessionid;

			*/

	}

	/**
	 * getting a complete list of objects
	 * @param  int $elementid to query a single object
	 * @return array        sql result with all available objects
	 */
	public function getObject($elementid) {

		// just right
		// // AND O1.ID = 703
		$select_t = 'SELECT O1.ID AS PARENTID, O1.NAME AS PARENTNAME, O1.TYPE AS PARENTTYPE, O2.ID AS CHILDID, O2.NAME AS CHILDNAME, O2.TYPE AS CHILDTYPE, O2.STATUS_ID AS CHILDSTATUSID, O2.CURRENT_VALUE AS CHILDSTATUSVALUE, O2.VALUES_TYPE as VALUES_TYPE
FROM DPADD_OBJECT_RELATION AS REL1
INNER JOIN DPADD_OBJECT AS O1 ON O1.ID = REL1.CHILDOBJ_ID AND O1.TYPE IN ("GROUP", "BYMEIDX")
INNER JOIN DPADD_OBJECT_RELATION AS REL2 ON O1.ID = REL2.PARENTOBJ_ID AND REL2.RELATION_WEB_TIPOLOGY = "GENERIC_RELATION"
INNER JOIN DPADD_OBJECT AS O2 ON O2.ID = REL2.CHILDOBJ_ID AND O2.TYPE IN ("GROUP", "BYMEIDX")
WHERE REL1.PARENTOBJ_ID IN (%d) AND REL1.RELATION_WEB_TIPOLOGY="USERGROUP_RELATION" AND O2.ID = %d
ORDER BY PARENTID, CHILDID, REL1.ORDER_NUM, REL2.ORDER_NUM;';

		$select = sprintf($select_t, intval($this->group_id), intval($elementid));

		$result = $this->querySQL($select);

		return $result;
	}

	/**
	 * getting a complete list of objects
	 * @return array        sql result with all available objects
	 */
	public function getObjectList() {
		/*

		// too less, we need more data at once
		$select_t = 'SELECT D_O.ID AS OBJECT_ID, D_O.NAME AS OBJECT_NAME, D_O.DESCRIPTION AS OBJECT_DESCRIPTION, D_O.TYPE AS OBJECT_TYPE, D_OR.PARENTOBJ_ID AS USERGROUP_ID FROM DPADD_OBJECT AS D_O INNER JOIN DPADD_OBJECT_RELATION AS D_OR ON (D_O.ID=D_OR.CHILDOBJ_ID) WHERE (D_OR.PARENTOBJ_ID IN (%d) AND D_OR.RELATION_WEB_TIPOLOGY=&apos;USERGROUP_RELATION&apos;) ORDER BY OBJECT_NAME;';


		// Too much, will kill webserver
		$select = '
SELECT O1.ID AS PARENTID, O1.NAME AS PARENTNAME, O1.TYPE AS PARENTTYPE, O2.ID AS CHILDID, O2.NAME AS CHILDNAME, O2.TYPE AS CHILDTYPE, REL3.OPTIONAL, O3.ID AS VALUEID, O3.NAME AS VALUENAME, O3.CURRENT_VALUE, O3.OPTIONALP, O3.IS_REMOTABLE
FROM DPADD_OBJECT_RELATION AS REL1
INNER JOIN DPADD_OBJECT AS O1 ON O1.ID = REL1.CHILDOBJ_ID AND O1.TYPE IN ("GROUP", "BYMEIDX")
INNER JOIN DPADD_OBJECT_RELATION AS REL2 ON O1.ID = REL2.PARENTOBJ_ID AND REL2.RELATION_WEB_TIPOLOGY = "GENERIC_RELATION"
INNER JOIN DPADD_OBJECT AS O2 ON O2.ID = REL2.CHILDOBJ_ID AND O2.TYPE IN ("GROUP", "BYMEIDX")
INNER JOIN DPADD_OBJECT_RELATION AS REL3 ON O2.ID = REL3.PARENTOBJ_ID AND REL3.RELATION_WEB_TIPOLOGY = "BYME_IDXOBJ_RELATION"
INNER JOIN DPADD_OBJECT AS O3 ON O3.ID = REL3.CHILDOBJ_ID AND O3.IS_REMOTABLE = 1
WHERE REL1.PARENTOBJ_ID IN (20) AND REL1.RELATION_WEB_TIPOLOGY="USERGROUP_RELATION" AND O2.ID = 704 AND O1.ID = 703;
';
		*/

		// just right
		// // AND O1.ID = 703
		$select_t = 'SELECT O1.ID AS PARENTID, O1.NAME AS PARENTNAME, O1.TYPE AS PARENTTYPE, O2.ID AS CHILDID, O2.NAME AS CHILDNAME, O2.TYPE AS CHILDTYPE, O2.STATUS_ID AS CHILDSTATUSID, O2.CURRENT_VALUE AS CHILDSTATUSVALUE, O2.VALUES_TYPE as VALUES_TYPE
FROM DPADD_OBJECT_RELATION AS REL1
INNER JOIN DPADD_OBJECT AS O1 ON O1.ID = REL1.CHILDOBJ_ID AND O1.TYPE IN ("GROUP", "BYMEIDX")
INNER JOIN DPADD_OBJECT_RELATION AS REL2 ON O1.ID = REL2.PARENTOBJ_ID AND REL2.RELATION_WEB_TIPOLOGY = "GENERIC_RELATION"
INNER JOIN DPADD_OBJECT AS O2 ON O2.ID = REL2.CHILDOBJ_ID AND O2.TYPE IN ("GROUP", "BYMEIDX")
WHERE REL1.PARENTOBJ_ID IN (%d) AND REL1.RELATION_WEB_TIPOLOGY="USERGROUP_RELATION"
ORDER BY PARENTID, CHILDID, REL1.ORDER_NUM, REL2.ORDER_NUM;';

		$select = sprintf($select_t, intval($this->group_id));

		$result = $this->querySQL($select);

		return $result;
	}


	/**
	 * converts the sql result into a BusElement list Structure
	 * sets $this->elements and fills adds elements
	 * @param  array $elementlist result from getObjectList
	 * @return void
	 */
	public function buildBusProject($elementlist) {
		$this->elements = array();

		if(!empty($elementlist)) {
			foreach ($elementlist as $idx => $row) {
				// current rows parentid is not yet in the projects list, add it
				if(!isset($this->elements[$row['PARENTID']])) {
					$element = new BusElement($row['PARENTID'], $row['PARENTNAME'], $row['PARENTTYPE'], $row['VALUES_TYPE']);
					$this->elements[$row['PARENTID']] = &$element;
				} /*else {
					$element = $this->elements[$row['PARENTID']];
				}*/

				if(!isset($this->elements[$row['CHILDID']])) {
					$child = new BusElement($row['CHILDID'], $row['CHILDNAME'], $row['CHILDTYPE'], $row['VALUES_TYPE']);
					$this->elements[$row['PARENTID']]->addChild($child);
					$this->elements[$row['CHILDID']] = $child;
				} /*else {
					$element = $this->elements[$row['CHILDID']];
				}*/
			}
		}

	}



	/**
	 * get all Status values for a list of element Ids
	 * @param  int[] $ids array or csv of element ids
	 * @return array        sql result with all available element status
	 */
	public function getValue($ids) {
			// O2.IS_REMOTABLE = 1 .. rollladen have is_remoteable = 0
			// AND O2.OPTIONALP != ""
		$select_t = 'SELECT O1.ID AS PARENTID, O1.NAME AS PARENTNAME, O1.TYPE AS PARENTTYPE, REL2.OPTIONAL, O2.ID as VALUEID, O2.NAME as VALUENAME, O2.CURRENT_VALUE, O2.OPTIONALP, O2.IS_REMOTABLE
FROM DPADD_OBJECT AS O1
INNER JOIN DPADD_OBJECT_RELATION AS REL2 ON O1.ID = REL2.PARENTOBJ_ID AND RELATION_WEB_TIPOLOGY = "BYME_IDXOBJ_RELATION"
INNER JOIN DPADD_OBJECT AS O2 ON O2.ID = REL2.CHILDOBJ_ID AND O2.TYPE = "BYMEOBJ"
WHERE O1.ID in (%s)
ORDER BY PARENTID;
';
		if(is_array($ids)) {
			$ids = implode(', ', $ids);
		}
		$select = sprintf($select_t, $ids);

		$result = $this->querySQL($select);

		// AND O2.NAME = "on/off" .. 1 an, 0 aus
		// AND O2.NAME = "value" .. xx% stärke vom dimmer

		return $result;
	}


	/**
	 * converts the sql result into update for the BusElement structure
	 * updates $this->elements with current element status
	 * @param  array $elementlist result from getValue
	 * @return void
	 */
	public function updateBusProject($statuslist) {

		if(!empty($statuslist)) {
			foreach ($statuslist as $idx => $row) {
				//echo "idx: $idx - parent: {$row['PARENTID']} - valueid: {$row['VALUEID']}\n";
				// current rows parentid is not yet in the projects list, add it
				if(!isset($this->elements[$row['PARENTID']])) {
					echo "ERROR: status for unknown element received: ". $row['PARENTID'] . ': '. print_r($row, true)."\n";
					continue;
				}

				// if this si a new status, create a status object
				if(!isset($this->elements[$row['PARENTID']][$row['VALUEID']])) {
					//echo "hier: PARENTID: {$row['PARENTID']} - valueid: {$row['VALUEID']}\n";
					$status = new BusStatus($row['VALUEID'], $row['VALUENAME'], $row['CURRENT_VALUE'], $row['OPTIONALP']);
					$this->elements[$row['PARENTID']][$row['VALUEID']] = $status;
					//echo "element: ". print_r($this->elements[$row['PARENTID']], true);
					//echo "status: ". $this->elements[$row['PARENTID']][$row['VALUEID']];
				} else {
					//echo "hier 2\n";
					$this->elements[$row['PARENTID']][$row['VALUEID']]->setStatus($row['VALUEID'], $row['VALUENAME'], $row['CURRENT_VALUE'], $row['OPTIONALP']);
				}

			}
		}
	}

	/**
	 * only returns those elements who can have a status queried
	 * TODO: check if key is still there
	 * TODO: check if arrayAccess is used here, or iterator
	 * @return array part of elements array
	 */
	public function getBusElements() {
		return array_filter ( $this->elements,
			function($element, $key) {
				return $element->type == 'BYMEIDX';
			}, ARRAY_FILTER_USE_BOTH
		);
	}



	############################### DEBUG ###############################
	/**
	 * get all fieldnames and types from a list of tables from the bus webserver.
	 * then created and show create table and insert statements, allowing to export the database.
	 * used only for debugging sql queries
	 * @return void
	 */
	public function queryGetTableInfo() {

		$showtables_t = 'SHOW TABLES LIKE \'%s\'';
		$createtable_t = 'PRAGMA table_info(%s);';
		$select_t = 'SELECT %s FROM %s';

		$tables = ['DPADD_OBJECT', 'DPADD_OBJECT_RELATION', 'DPAD_WEB_PHPCLASS', 'DPADD_RENDERING', 'DPADD_RENDERING_VIEW_PROTOTYPE'];

		//$tables = ['DPADD_OBJECT'];

		$showtables = sprintf($showtables_t, "%DPADD%");
		echo $showtables;
		$showtables_result = $this->querySQL($showtables);
		print_r($showtables_result);
		return;

		foreach ($tables as $idx => $table) {
			// create table statement
			$createtable = sprintf($createtable_t, $table);
			$fieldlist_result = $this->querySQL($createtable);
			$createtable = $this->generateCreateTable($table, $fieldlist_result);

			echo $createtable;

			// get list of fields
			$fieldlist = array_map(function($row) {
				return $row['name'];
			}, $fieldlist_result);

			// query each field from $table
			$select = sprintf($select_t, implode(", ", $fieldlist), $table);
			//$select = sprintf($select_t, '*', $table);
			//var_dump($select);
			$result = $this->querySQL($select); //. " limit 186, 2");
			//var_dump($result);

			// generate sql inserts
			$insert = $this->generateInserts($table, $result);

			echo $insert;
			echo "\n\n";
		}

		//return $result;
	}

	/**
	 * generated create table statements with given data
	 * used only for debugging sql queries
	 * @param  string $tablename name of the table
	 * @param  array $tableinfo field names and types
	 * @return string   create table statement
	 */
	protected function generateCreateTable($tablename, $tableinfo) {

		// `tx_ddgooglesitemap_priority` int(3) NOT NULL DEFAULT '5',
		//$createfield_t = "    `%s` %s %s %s, \n";
		$createfield_t = "  %s %s %s %s, \n";
		$createfields = "";
		$keys = "";

		foreach ($tableinfo as $idx => $rowdef) {

			// reset
			$name = $default = $type = $null = '';

			$name = trim($rowdef['name']);


			if($rowdef['type'] == 'INTEGER') {
				$type = "int(11)";

				if($rowdef['dflt_value'] !== '') {
					$default = 'DEFAULT '. intval($rowdef['dflt_value']);
				}
			} else if($rowdef['type'] == 'INTEGER(1)') {
				$type = "tinyint(4)";

				if($rowdef['dflt_value'] !== '') {
					$default = 'DEFAULT '. intval($rowdef['dflt_value']);
				}
			}

			else if($rowdef['type'] == 'STRING') {
				$type = "varchar(100)";
				$default = "DEFAULT '". addslashes($rowdef['dflt_value']) ."'";
			}
			else if($rowdef['type'] == 'TEXT') {
				$type = "VARCHAR(1000)";
				$default = "DEFAULT '". addslashes($rowdef['dflt_value']) ."'";
			}

			if(!empty($rowdef['notnull'])) { $null = "NOT NULL"; }

			if(!empty($rowdef['pk'])) {
				$default = "AUTO_INCREMENT";
				//$keys = " PRIMARY KEY (`$name`)";
				$keys = "   PRIMARY KEY ($name)";
			}

			if(substr($name, -3) === '_ID' && $rowdef['type'] !== 'TEXT') {
				$keys .= ",
	KEY ($name)";
			}

			$createfields .= sprintf($createfield_t, $name, $type, $null, $default);
		}

		$create_t = "DROP TABLE %s;
CREATE TABLE IF NOT EXISTS %s (
%s%s
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

";

		$create = sprintf($create_t, $tablename, $tablename, $createfields, $keys);

		return $create;
	}

	/**
	 * generates insert statements for given table
	 * used only for debugging sql queries
	 * @param  string   $tablename  table name
	 * @param  array    $selectinfo array of data
	 * @return string               sql insert statements
	 */
	protected function generateInserts($tablename, $selectinfo) {
		/*
		INSERT INTO tbl_name
			(a,b,c)
		VALUES
			(1,2,3),
			(4,5,6),
			(7,8,9);
		 */
		$insert_t = "INSERT INTO %s
(%s)
VALUES
%s;";
		$value_array = [];
		//var_dump($selectinfo);

		foreach ($selectinfo as $idx => $value_row) {

			if(!is_array($value_row)) {
				echo 'id: '. $idx . "\n";
				var_dump($value_row);
				continue;
			}
			$value_list = array_map(function($value) {
				return "'". addslashes($value) ."'";
			}, $value_row);

			$value_array[] = "(". implode(', ', $value_list) . ")";
		}

		$fieldlist = array_keys($selectinfo[0]);
		$insert = sprintf($insert_t,
			$tablename,
			implode(", ", $fieldlist),
			implode(",\n", $value_array)
		);

		return $insert;
	}
}
