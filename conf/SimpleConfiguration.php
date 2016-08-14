<?php
/*
 *    SimpleConfiguration Class v1.0: JSON based configuration file parser
 *    Copyright (C) 2016 Jon Stockton
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * SimpleConfiguration Class - Used to maintain a configuration objeect
 *
 * @package SimpleConfiguration
 */
class SimpleConfiguration implements ArrayAccess
{
	/**
	 * Instance of this class
	 *
	 * @var SimpleConfiguration
	 */
	protected static $instance = null;

	/**
	 * Internal configuration array
	 *
	 * @var array
	 */
	protected $configs = array();

	/**
	 * List of dynamic configuration aliases
	 *
	 * @var array
	 */
	protected $dynamicConfigs = array();

	/**
	 * Constructor
	 *
	 * @param string $config_directory The full path to the directory containing parse and noparse
	 * @return void
	 */
	protected function __construct($config_directory)
	{
		$parseDirPath = $config_directory."/parse";
		$config_files = scandir($parseDirPath);

		// Load up all of the configs
		foreach($config_files as $file) {
			if(substr($file, -5, 5) == ".json") {
				$subconf = substr($file, 0, -5);
				$config_info = json_decode(file_get_contents($parseDirPath."/".$file), true);
				if($subconf == "base") {
					foreach($config_info as $property => $value) {
						$this[$property] = $value;
					}
				}
				else {
					$this[substr($file, 0, -5)] = $config_info;
				}
			}
		}

		// Determine which configs have dynamic values
		foreach($this->configs as $subconfig => $properties) {
			if(is_array($properties)) {
				foreach($properties as $property => $value) {
					if(is_array($value)) {
						$this->dynamicConfigs[] = "this.${subconfig}.${property}";
					}
				}
			}
		}

		$this->configs["base"] = &$this->configs;
	}

	/**
	 * Parse dynamic configurations to consolidate them into a string
	 *
	 * @param mixed $config The config that's currently being parsed
	 * @param bool $resolve Whether or not to resolve the portion as an alias or leave it as is
	 * @return mixed Void if $config isn't passed and the resulting value if it is
	 */
	protected function parseDynamicConfigs($config = null, $resolve = false)
	{
		if(is_null($config)) {
			foreach($this->dynamicConfigs as $config) {
				$val = "";
				$expandedConfig = self::getVariableByAlias($config);
				foreach($expandedConfig as $portion) {
					if(is_array($portion)) {
						$val .= $this->parseDynamicConfigs($portion, true);
					}
					else {
						$val .= $portion;
					}
				}
				self::setVariableByAlias($config, $val);
			}
		}
		else {
			$val = "";
			if(is_array($config)) {
				if(isset($config["check"])) {
					$matches = array();
					if(isset($config["check"][1])) {
						if(preg_match("/{(.*)}/si", $config["check"][1], $matches)) {
							$config["check"][1] = self::getVariableByAlias($matches[1]);
						}
					}

					if(isset($config["check"][2])) {
						if(preg_match("/{(.*)}/si", $config["check"][2], $matches)) {
							$config["check"][2] = self::getVariableByAlias($matches[2]);
						}
					}

					if($this->parseCheck($config["check"])) {
						return $this->parseDynamicConfigs($config["true"]);
					}
					else {
						return $this->parseDynamicConfigs($config["false"]);
					}
				}
				else {
					foreach($config as $portion) {
						if(is_array($portion)) {
							$val .= $this->parseDynamicConfigs($portion, true);
						}
						else {
							if($resolve) {
								$portionVal = self::getVariableByAlias($portion);

								// Avoids a race condition where the configuration this one is based on is not parsed yet
								if(is_array($portionVal)) {
									$portionVal = $this->parseDynamicConfigs($portionVal);
								}
								$val .= $portionVal;
							} else {
								$val .= $portion;
							}
						}
					}
					return $val;
				}
			}
			else {
				return self::getVariableByAlias($config);
			}
		}
	}

	/**
	 * Parse conditional configuration checks
	 *
	 * @param array $check The check to be performed
	 * @return string The value to set as the configuration
	 */
	protected function parseCheck($check)
	{
		switch($check[0])
		{
			case "=":
				echo "This is a check for ${check[0]} '${check[1]}' '${check[2]}'\n";
				if($check[1] == $check[2]) {
					return true;
				}
				else {
					return false;
				}
				break;
			case "<>":
				if($check[1] != $check[2]) {
					return true;
				}
				else {
					return false;
				}
				break;
			case "<":
				if($check[1] < $check[2]) {
					return true;
				}
				else {
					return false;
				}
				break;
			case ">":
				if($check[1] > $check[2]) {
					return true;
				}
				else {
					return false;
				}
				break;
			case "<=":
				if($check[1] <= $check[2]) {
					return true;
				}
				else {
					return false;
				}
				break;
			case ">=":
				if($check[1] >= $check[2]) {
					return true;
				}
				else {
					return false;
				}
				break;

			// If we don't match any comparison operators, consider it true
			default:
				return true;
		}
	}

	/**
	 * Reload the configurations
	 *
	 * @param mixed $config_directory The directory containing parse and noparse (defaults to the current config_directory)
	 * @return SimpleConfiguration The instance of this class
	 */
	public static function reload($config_directory = null)
	{
		$configs = self::instance();
		if(is_null($config_directory)) {
			$config_directory = $configs->config_directory;
		}

		return self::instance($config_directory, true);
	}

	/**
	 * Retrieve the value of a configuration based on it's alias
	 *
	 * @param string $alias The configuration variables alias
	 * @return mixed The value of the configuration
	 */
	public static function getVariableByAlias($alias)
	{
		$expanded = explode(".", $alias);
		$val = null;
		switch($expanded[0])
		{
			case "this":
				$val = self::$instance;
				break;
			case "server":
				$val = $_SERVER;
				break;
			case "get":
				$val = $_GET;
				break;
			case "post":
				$val = $_POST;
				break;
			case "session":
				$val = $_SESSION;
				break;
			default:
				return null;
		}

		for($i = 1; $i < count($expanded); $i++) {
			if(isset($val[$expanded[$i]])) {
				$val = $val[$expanded[$i]];
			}
			else {
				$val = null;
				break;
			}
		}
		return $val;
	}

	/**
	 * Set a configuration variable based on it's  alias
	 *
	 * @param string $alias The variables alias
	 * @param mixed $val The value to set it to
	 * @return bool Whether ot not he operation succeeded
	 */
	public static function setVariableByAlias($alias, $val)
	{
		$expanded = explode(".", $alias);
		switch($expanded[0])
		{
			case "this":
				$reference = &self::$instance->configs;
				break;
			case "server":
				$reference = &$_SERVER;
				break;
			case "get":
				$reference = &$_GET;
				break;
			case "post":
				$reference = &$_POST;
				break;
			case "session":
				$reference = &$_SESSION;
				break;
			default:
				return false;
		}
		unset($expanded[0]);
		foreach($expanded as $expander) {
			$reference = &$reference[$expander];
		}
		$reference = $val;
		return true;
	}

	/**
	 * Get an instance of this class (in a Singleton fashion)
	 *
	 * @param string $config_directory The directory that contains parse and noparse
	 * @param bool $force_reload Whether or not to force a reload of the instance
	 * @return SimpleConfiguration The instance that was created during this run or a previous one
	 */
	public static function instance($config_directory = __DIR__, $force_reload = false)
	{
		if(is_null(self::$instance) || $force_reload) {
			self::$instance = new SimpleConfiguration($config_directory);
			self::$instance->parseDynamicConfigs();
			self::$instance->config_directory = $config_directory;
		}
		return self::$instance;
	}

	/**
	 * Inherited from ArrayAccess
	 *   Checks if an index exists in the array object
	 *
	 * @param mixed $offset The index to check for
	 * @return bool True if it exists, false if not.
	 */
	public function offsetExists($offset)
	{
		return isset($this->configs[$offset]);
	}

	/**
	 * Inherited from ArrayAccess
	 *   Gets the value at the specified index
	 *
	 * @param mixed $offset The index to retrieve the value from
	 * @return mixed The value that is at the index
	 */
	public function offsetGet($offset)
	{
		return $this->configs[$offset];
	}

	/**
	 * Inherited from ArrayAccess
	 *   Sets the value at the speciifed index
	 *
	 * @param mixed $offset The index to set the value of
	 * @param mixed $value The value to set
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->configs[$offset] = $value;
		return;
	}

	/**
	 * Inherited from ArrayAccess
	 *   Unsets the value at the speciifed index
	 *
	 * @param mixed $offset The index to unset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->configs[$offset]);
		return;
	}
}
