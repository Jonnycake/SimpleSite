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
 
if(SIMPLESITE!=1)
	die("Can't access this file directly.");
abstract class SimpleModule extends SimpleDisplay implements simpleModuleI
{
	public static $info=array(
				"name"    => "Default",
				"version" => "0.1",
				"author"  => "Default",
				"date"    => "Default"
			);
	public static $debug=false;

	// Abstract methods
	abstract public function isInstalled();
	abstract public function install();
	abstract public function uninstall();

	// Default overloadable methods
	public function choosePage()
	{
		return "";
	}
	public function getContent()
	{
		return "";
	}
	public function sideparse($content)
	{
		return $content;
	}

	// Magic Methods
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
