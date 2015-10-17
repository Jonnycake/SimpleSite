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
		$this->loadModules($this->configs);
		if((in_array($name,$this->mods)) && !(in_array($name,$this->loaded))) // Disabled modules aren't put into $this->mods
		{
			if(file_exists($_SERVER['DOCUMENT_ROOT'].$this->configs['path']['root']."includes/mods/enabled/${name}.mod.php"))
			{
				include($_SERVER['DOCUMENT_ROOT'].$this->configs['path']['root']."includes/mods/enabled/${name}.mod.php");
				if(!(class_exists($name)))
				{
					SimpleDebug::logInfo("Error!");
				}
				else
				{
					$this->loaded[]=$name;
					SimpleDebug::logInfo("Good.");
				}
			}
			else
			{
				SimpleDebug::logInfo("Error!");
			}
		}
		else if($loadDisabled && !(in_array($name, $this->loaded)))
		{
			if(file_exists($_SERVER['DOCUMENT_ROOT'].$this->configs['path']['root']."includes/mods/disabled/${name}.mod.php"))
			{
				include($_SERVER['DOCUMENT_ROOT'].$this->configs['path']['root']."includes/mods/disabled/${name}.mod.php");
				if(!(class_exists($name)))
				{
					SimpleDebug::logInfo("Error!");
				}
				else
				{
					$this->loaded[]=$name;
					SimpleDebug::logInfo("Good.");
				}
			}
			else
			{
				SimpleDebug::logInfo("Could not find disabled module...creating false class.");
				eval("class $name { public static \$info=array(\"name\" => \"$name (Unknown Module)\", \"author\" => \"Unknown Module\", \"date\" => \"Unknown Module\"); }");
			}
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
