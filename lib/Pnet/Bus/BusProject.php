<?php
namespace Pnet\Bus;

use Pnet\Bus\BusStatus;
use Pnet\Bus\BusElement;

/**
 * BusProject
 * Each actor as well as each room is a BusElement
 * it has ID, name, type and can have BusElements as childs
 *
 */
class BusProject {
	protected $elements = array();

	/**
	 * create a new BusProject
	 */
	function __construct() {

	}

	public function addElement($element) {
		if(empty($element['object_id'])) {
			// TODO - add proper error handling
			return false;
		}

		if(empty($this->elements[$element['object_id']])) {
			$this->elements[$element['object_id']] = new BusElement($element['object_id'], $element['name']);
		}
	}

}
