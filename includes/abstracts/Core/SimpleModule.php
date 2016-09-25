<?php
/*
 *    SimpleModule v1.0: Module's default properties and methods.
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
 * Definition of the SimpleModule abstract class
 *
 * @package SimpleSite Core
 * @author Jonathan Stockton <jonathan@simplesite.ddns.net>
 */

/**
 * Can't be accessed directly
 */
if(SIMPLESITE!=1)
	die("Can't access this file directly.");

/**
 * Abstract class for modules to extend from
 *
 * @todo Check why simpleModuleI doesn't specify $content for sideparse
 */
abstract class SimpleModule extends SimpleDisplay implements simpleModuleI
{
	/**
	 * Give basic information about the module here
	 *
	 * @var array $info
	 */
	public static $info=array(
				"name"    => "Default",
				"version" => "0.1",
				"author"  => "Default",
				"date"    => "Default"
			);

	/**
	 * Whether or not to execute in debug mode
	 *
	 * @var bool $debug
	 */
	public static $debug=false;

	// Abstract methods
	/**
	 * Should check if the module has everything it needs to run properly
	 *
	 * @return bool Whether or not it is installed properly
	 */
	abstract public function isInstalled();

	/**
	 * Should install everything the module needs to run properly
	 *
	 * @return bool Whether or not it could be installed automatically
	 */
	abstract public function install();

	/**
	 * Should uninstall the files and database tables that would have been created
	 *
	 * @return bool Whether or not it could be uninstalled
	 */
	abstract public function uninstall();

	// Default overloadable methods
	public function api($route, $configs = array())
	{
		return null;
	}

	/**
	 * Choose what page to use, default behavior is an empty string (default page)
	 */
	public function choosePage()
	{
		return "";
	}

	/**
	 * Returns nothing for the {CONTENT} replacement
	 */
	public function getContent()
	{
		return "";
	}

	/**
	 * Do side-parsing (default is no change)
	 *
	 * @param string $content the content to parse
	 * @return string The parsed content
	 */
	public function sideparse($content)
	{
		return $content;
	}

	// Magic Methods
	/**
	 * Default constructor for modules
	 *
	 * Checks if the module is installed and if not tries to install it
	 *
	 * @param array $configs The configurations set in config.inc.php
	 * @param SimpleDB|null $db The database object to be used
	 * @param bool $debug Whether or not to be in debug mode. 
	 */
	public function __construct($configs=array(), $db=null, $debug=false)
	{
		// Initialize some properties
		$this->debug=$debug;
		$this->configs=$configs;
		$this->db=$db;

		SimpleDebug::logInfo("\$obj->isInstalled()...");

		if(!$this->isInstalled($this->configs))
		{
			SimpleDebug::logInfo("Not installed...attempting install...");
			if($this->install($this->configs))
			{
				if($this->isInstalled($this->configs))
				{
					SimpleDebug::logInfo("Installed.");
				}
				else
				{
					SimpleDebug::logInfo("Failed install.");
				}
			}
			else
			{
				SimpleDebug::logInfo("Can not automatically install this module.");
			}
		}
		else
		{
			SimpleDebug::logInfo("Installed.");
		}
	}
}
?>
