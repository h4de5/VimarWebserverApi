<?php
namespace Pnet\Bus;

/**
 * Each element can have a status
 */
class BusStatus {
	/**
	 * @var mixed
	 */
	public $id;
	/**
	 * @var mixed
	 */
	public $type;
	/**
	 * @var mixed
	 */
	public $value;
	/**
	 * @var mixed
	 */
	public $rule;

	/**
	 * @param $id
	 * @param $type
	 * @param $value
	 * @param $rule
	 */
	public function __construct($id, $type = '', $value = '', $rule = '') {
		$this->setStatus($id, $type, $value, $rule);
	}

	/**
	 * @param $id
	 * @param $type
	 * @param $value
	 * @param $rule
	 */
	public function setStatus($id, $type, $value, $rule) {
		$this->id = $id;
		$this->type = $type;
		$this->value = $value;
		$this->rule = $rule;
	}

	############################### DEBUG ###############################

	/**
	 * @return mixed
	 */
	public function __toString() {
		$ret = '#' . $this->id . ' ' . $this->type
		. ': ' . $this->value;
		return $ret;
	}

	public function __debugInfo() {
		return [
			'status' => $this->__toString()];
	}
}
