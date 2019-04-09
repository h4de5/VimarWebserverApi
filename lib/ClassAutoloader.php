<?php
namespace Pnet\Bus;

class ClassAutoloader {
	public function __construct() {

		spl_autoload_register(array($this, 'loader'));
	}

	private function loader($className)  {
		//echo '$className: '. $className .'<br />';

		$className = str_replace(__NAMESPACE__.'\\', '', $className);
		$file = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

		#echo '$file: '. __DIR__.DIRECTORY_SEPARATOR.$file .'<br />';
		#echo 'file_exists: '. file_exists(__DIR__.DIRECTORY_SEPARATOR.$file) .'<br />';

		if (!file_exists(__DIR__.DIRECTORY_SEPARATOR.$file)) {
			return false;
		} else {
			require __DIR__.DIRECTORY_SEPARATOR.$file;
			return true;
		}
	}
}
