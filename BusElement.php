<?php
namespace Pnet\Bus;

use Pnet\Bus\BusStatus; 

class BusElement implements \Iterator, \ArrayAccess {
	public $id;
	public $name;
	public $childs;
	public $status;
	public $type;
	public $values_type;

	private $position = 0;

	function __construct($id, $name, $type, $values_type, $childs = array(), $status = array()) {
		$this->id = $id;
		$this->name = $name;
		$this->type = $type;
		$this->values_type = $values_type;
		$this->childs = $childs;
		$this->status = $status;

		$this->position = 0;
	}

	public function isOn() {
		return $this->getStatusData("on/off");
	}

	public function getStatusData($statusname) {
		$status = $this->getStatus($statusname);
		if($status != null) {
			return $status->value;
		}
		return $status;
	}

	public function getStatusId($statusname) {
		$status = $this->getStatus($statusname);
		if($status != null) {
			return $status->id;
		}
		return $status;
	}

	public function getStatus($statusname) {
		if(empty($this->status)) {
			return null;
		}
		foreach ($this->status as $idx => $status) {
			if($status->type == $statusname) {
				return $status;
			} 
		}
		return null;
	}

	public function getName() {
		return mb_convert_case($this->name, MB_CASE_TITLE, 'UTF-8');
	}


	/**
	 * add a child to this element
	 * @param BusElement $element child element
	 * @return &BusElement reference to the buselement, used for the list in the project
	 */
	public function addChild($element) {
		$this->childs[] = $element;
		//return &$this->childs[count($this->childs)-1];
	}
	public function addStatus($key, $value) {
		//$this->status[$key] = $value;
		$this->offsetSet($key, $value);
	}

	/** ArrayAccess methods */
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->status[] = $value;
		} else {
			$this->status[$offset] = $value;
		}
	}

	public function offsetExists($offset) {
		return isset($this->status[$offset]);
	}

	public function offsetUnset($offset) {
		unset($this->status[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->status[$offset]) ? $this->status[$offset] : null;
	}


	/** Iterator methods */
	function rewind() {
		$this->position = 0;
	}
	function current() {
		return $this->childs[$this->position];
	}
	function key() {
		return $this->position;
	}
	function next() {
		++$this->position;
	}
	function valid() {
		return isset($this->childs[$this->position]);
	}


	############################### DEBUG ###############################
	public function __toString() {
		$ret = '#'. $this->id .' '. $this->name 
			.' ('. $this->type .')';
			
		if(count($this->childs) > 0) {
			$ret .= ' - '. count($this->childs) .' child'.(count($this->childs) > 1 ? 's' : '');
		}
		
		if(count($this->status) > 0) {
			//$val = reset($this->status);
			//$key = key($this->status);

			//$ret .= ' / '. $val;
		}
		return $ret ;
	}

	public function __debugInfo() {
		$ret = [
			'element' => '#'. $this->id .' '. $this->name 
			.' ('. $this->type .')']
		;
		if(count($this->childs) > 0) {
			$ret['childs'] = $this->childs;
		}
		if(count($this->status) > 0) {
			$ret['status'] = $this->status;
		}

		return $ret;
	}

}
