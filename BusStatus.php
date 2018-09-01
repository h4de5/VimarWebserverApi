<?php
namespace Pnet\Bus;

class BusStatus {
	public $id;
	public $type;
	public $value;
	public $rule;

	function __construct($id, $type='', $value='', $rule='') {
		$this->setStatus($id, $type, $value, $rule );
	}

	public function setStatus($id, $type, $value, $rule ) {
		$this->id = $id;
		$this->type = $type;
		$this->value = $value;
		$this->rule = $rule;
	}

	############################### DEBUG ###############################
	
	public function __toString() {
		$ret = '#'. $this->id .' '. $this->type 
			.': '. $this->value;
		return $ret;
	}

	public function __debugInfo() {
		return [
			'status' => $this->__toString()];
	}
}