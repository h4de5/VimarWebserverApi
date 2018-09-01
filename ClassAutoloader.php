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
		
		//echo '$file: '. $file .'<br />';
		//echo 'file_exists: '. file_exists($file) .'<br />';

		if (!file_exists($file)) {
			return false;
		} else {
			require $file;
			return true;
		}
	}
}
