<?php
namespace Pnet\Bus\Model;

use Pnet\Bus\BusStatus;

/**
 * BusElement
 * Each actor is a BusElement
 * it has an ID, name, type, status and belongs to one or more rooms
 *
 */
class BusElement implements \Iterator, \ArrayAccess {
	/**
	 * @var mixed
	 */
	public $id;
	/**
	 * @var mixed
	 */
	public $name;
	/**
	 * @var BusStatus
	 */
	public $status;
	/**
	 * @var mixed
	 */
	public $type;

	/**
	 * @var array
	 */
	public $rooms;

	/**
	 * @var mixed
	 */
	public $values_type;
	/**
	 * @var int
	 */
	private $position = 0;

	/**
	 * create a new BusElement
	 *
	 * @param [type] $id
	 * @param [type] $name
	 * @param [type] $type
	 * @param [type] $values_type
	 * @param array $childs
	 * @param array $status
	 */
	public function __construct($id, $name, $type, $values_type, $childs = [], $status = []) {
		$this->id = $id;
		$this->name = $name;
		$this->type = $type;
		$this->values_type = $values_type;
		$this->childs = $childs;
		$this->status = $status;

		$this->position = 0;
	}

	/**
	 * @return mixed
	 */
	public function isOn() {
		return $this->getStatusData("on/off");
	}

	/**
	 * @param $statusname
	 * @return mixed
	 */
	public function getStatusData($statusname) {
		$status = $this->getStatus($statusname);
		if (null != $status) {
			return $status->value;
		}
		return $status;
	}

	/**
	 * @param $statusname
	 * @return mixed
	 */
	public function getStatusId($statusname) {
		$status = $this->getStatus($statusname);
		if (null != $status) {
			return $status->id;
		}
		return $status;
	}

	/**
	 * @param $statusname
	 * @return mixed
	 */
	public function getStatus($statusname) {
		if (empty($this->status)) {
			return null;
		}
		foreach ($this->status as $idx => $status) {
			if ($status->type == $statusname) {
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
	/**
	 * @param $key
	 * @param $value
	 */
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

	/**
	 * @param $offset
	 */
	public function offsetExists($offset) {
		return isset($this->status[$offset]);
	}

	/**
	 * @param $offset
	 */
	public function offsetUnset($offset) {
		unset($this->status[$offset]);
	}

	/**
	 * @param $offset
	 */
	public function offsetGet($offset) {
		return isset($this->status[$offset]) ? $this->status[$offset] : null;
	}

	/** Iterator methods */
	public function rewind() {
		$this->position = 0;
	}
	/**
	 * @return mixed
	 */
	public function current() {
		return $this->childs[$this->position];
	}
	/**
	 * @return mixed
	 */
	public function key() {
		return $this->position;
	}
	public function next() {
		++$this->position;
	}
	public function valid() {
		return isset($this->childs[$this->position]);
	}

	############################### DEBUG ###############################
	/**
	 * @return mixed
	 */
	public function __toString() {
		$ret = '#' . $this->id . ' ' . $this->name
		. ' (' . $this->type . ')';

		if (count($this->childs) > 0) {
			$ret .= ' - ' . count($this->childs) . ' child' . (count($this->childs) > 1 ? 's' : '');
		}

		if (count($this->status) > 0) {
			//$val = reset($this->status);
			//$key = key($this->status);

			//$ret .= ' / '. $val;
		}
		return $ret;
	}

	/**
	 * @return mixed
	 */
	public function __debugInfo() {
		$ret = [
			'element' => '#' . $this->id . ' ' . $this->name
			. ' (' . $this->type . ')']
		;
		if (count($this->childs) > 0) {
			$ret['childs'] = $this->childs;
		}
		if (count($this->status) > 0) {
			$ret['status'] = $this->status;
		}

		return $ret;
	}

}
