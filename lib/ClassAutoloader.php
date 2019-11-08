<?php

class ClassAutoloader {
	public function __construct() {

		spl_autoload_register([$this, 'loader']);
	}

	/**
	 * @param $className
	 */
	private function loader($className) {
		//echo '$className: '. $className .'<br />';

		$className = ltrim($className, '\\');
		$fileName = '';
		$namespace = '';
		if ($lastNsPos = strrpos($className, '\\')) {
			$namespace = substr($className, 0, $lastNsPos);
			$className = substr($className, $lastNsPos + 1);
			$fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

		// $className = str_replace(__NAMESPACE__.'\\', '', $className);
		// $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

		// echo '$fileName: '. __DIR__.DIRECTORY_SEPARATOR.$fileName .'<br />';
		// echo 'file_exists: '. file_exists(__DIR__.DIRECTORY_SEPARATOR.$fileName) .'<br />';

		if (!file_exists(__DIR__ . DIRECTORY_SEPARATOR . $fileName)) {
			return false;

		} else {
			require __DIR__ . DIRECTORY_SEPARATOR . $fileName;
			return true;
		}
	}

}
