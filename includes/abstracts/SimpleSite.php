<?php
/*
 *    SimpleSite Main Class v2.1: Main program logic.
 *    Copyright (C) 2014 Jon Stockton
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
 * Abstract class for controllers
 *
 * @package SimpleSite Core
 * @author Jonathan Stockton <jonathan@simplesite.ddns.net>
 */

/**
 * Can't be accessed directly.
 */
if(SIMPLESITE!=1)
	die("Can't access this file directly.");

/**
 * Abstract class of controllers
 */
abstract class SimpleSite extends SimpleDisplay
{
	/**
	 * Abstract constructor
	 *
	 * Most of the time will just initialize constants, etc. and do showSite()
	 */
	abstract function __construct();

	/**
	 * Function to register for autoloading
	 *
	 * @param string $name The name of the class to load
	 * @return void
	 */
	public function simpleLoader($name)
	{
		global $loadDisabled; // We could probably also use something like this instead of the singleton pattern (global $reloadMods or something) - look into performance comparison
		SimpleDebug::logInfo("Attempting to autoload $name...");
		$this->loadComponents($this->configs);
		// Check modules
		if(array_key_exists($name, $this->mods)) {
			if($this->mods['name']['enabled'] || $loadDisabled) {
				include($configs['path']['mods'] . (($this->mods['name']['enabled'])?"enabled":"disabled")."/${name}.mod.php");
			}
		}
		// Check components
		else if(array_key_exists($name, self::$include_file_list)) {
			include(self::$include_file_list[$name]);
		}
	}

	/**
	 * Magic call method
	 *
	 * Currently used for widgets, will be switched to being used for plugins
	 *
	 * @param string $method The name of the method to call
	 * @param array $args The arguments passed to the function
	 * @return mixed The result of calling the anonymous function
	 */
	public function __call($method, $args)
	{
		SimpleDebug::logInfo("SimpleSite->__call($method,\$args)");
		if(@(isset($this->$method) && is_callable($this->$method)))
		{
			$func = $this->$method;
			return $func($args);
		}
		else
			throw new Exception("Bad function name.");
	}

	/**
	 * Destructor
	 *
	 * @return void
	 */
	function __destruct()
	{
		$this->db->__destruct();
	}
}
?>
