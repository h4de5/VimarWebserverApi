<?php
namespace Pnet\Bus;

class Config {

	private $configPath;
	private $config = [];

	function __construct($configPath) {
		$this->configPath = $configPath;
	}

	public function load() {
		$this->config = parse_ini_file($this->configPath, true);
		return $this;
	}

	public function validate() {
		assert(
			!empty($this->config['auth']['host']),
			new \AssertionError("Missing host setting in configuration - check config.ini")
		);
		assert(
			!empty($this->config['auth']['user']),
			new \AssertionError("Missing user setting in configuration - check config.ini")
		);
		assert(
			!empty($this->config['auth']['password']),
			new \AssertionError("Missing password setting in configuration - check config.ini")
		);
		return $this;
	}

	public function get() {
		return $this->config;
	}
}
