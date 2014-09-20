<?php
/*
 *    SimpleDebug 0.1: Basic debugging/logging functions.
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

class SimpleDebug
{
	// Array of settings to be used to update and create instances
	public static $settings=array(
					"mode"        => 0,
					"errorLevel"  => 0,
					"format"      => "Dbg (Module: {MOD}): {LINENUM} {MESSAGE} (Error Level: {ERRLVL})",
					"time_format" => null,
					"file_path"   => "{logdir}"
				);

	// Should be able to set the file path as well as the file name and any prefix/suffixes
	// Should be able to change log message format (formatted based on variables)

	public static staticLog=array("Info" => array(), "Depends" => array(), "Exception" => array());

	// Configuration Functions
	public static function setDbgMode($mode)
	{
	}

	public static function getDbgMode()
	{
	}

	public static function addDepends($depends_name, $dependCheck, $side_effects, $feature_type="core", $feature_name=null)
	{
	}

	public static function checkDepends()
	{
	}

	// Logging Functions
	public static function __callStatic($method, $args)
	{
		switch($method)
		{
			case "logException":
				break;
			case "logInfo":
				break;
			case "logDepends":
				break;
			default:
				echo "Error";
				break;
		}
	}

	// Output
	public static function printLog() // Output log
	{
	}

	public static function stackTrace() // Output stack trace
	{
	}

	public static function saveLog() // Save log to log file
	{
	}


	/*
	 * This section should be used for code related to using the class in a non-static
	 * way so that modules, etc. can create their own logs without affecting other
	 * componets.
	 */

	// Array of named-instances created/retrieved/destroyed using the functions below
	public static $instances=null;

	public static function createInstance($instanceName)
	{
		if(self::$instances==null)
			self::$instances=array();

		if(!array_key_exists($instanceName, self::$instances))
		{
			self::$instances[$instanceName]=new self(self::$settings);
		}
		return self::$instances[$instanceName];
	}
	public static function destroyInstance($instanceName)
	{
		unset(self::$instances[$instanceName]);
	}
	public static function getInstance($instanceName)
	{
		if(isset(self::$instances[$instanceName]))
			return self::$instances[$instanceName];
		else
			return self::createInstance($instanceName);
	}

	// Instance Configurations
	// Current debug mode
	private $mode=0;

	// Keep track of problems so that there's one point of contact for components to know what to do
	private $errorLevel=0;


	// Log output format
	private $format="Dbg (Module: {MOD}): {LINENUM} {MESSAGE} (Error Level: {ERRLVL})";

	// Time output format
	private $time_format=null;

	public function __construct($settings)
	{
	}
	public function instanceLogInfo($info)
	{
	}
	public function __call($method, $args) // Needs to handle static calls as well in case of pre-5.3.0
	{
		switch($method)
		{
			case "logException":
				break;
			case "logInfo":
				break;
			case "logDepends":
				break;
			default:
				echo "Error";
				break;
		}
	}
}
?>
