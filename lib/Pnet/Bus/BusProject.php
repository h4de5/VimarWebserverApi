<?php
namespace Pnet\Bus;

use Pnet\Bus\BusStatus;
use Pnet\Bus\Model\BusElement;

/**
 * BusProject
 * elements belong to a project
 *
 */
class BusProject {
	/**
	 * @var array
	 */
	protected $elements = [];

	/**
	 * create a new BusProject
	 */
	public function __construct() {}

	/**
	 * @param $element
	 */
	public function addElement($element) {
		if (empty($element['object_id'])) {
			// TODO - add proper error handling
			return false;
		}

		if (empty($this->elements[$element['object_id']])) {
			$this->elements[$element['object_id']] = new BusElement($element['object_id'], $element['name']);
		}
	}

}
