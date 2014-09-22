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

	public static $event_id=0;
	public static $log=null;

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

	public static function logException($e)
	{
		self::logEvent("Exception", $e);
	}
	public static function logInfo($info)
	{
		self::logEvent("Info", $info);
	}
	public static function logDepends($depends)
	{
		self::logEvent("Depends", $depends);
	}
	public static function logEvent($type, $info)
	{
		if(is_null(self::$log))
			self::$log=array( "Exception"=>array(), "Info"=>array(), "Depends"=>array() );
		self::$log[$type][]=array("event_id"=>self::$event_id++, "type"=>$type, "time"=>time(), "message"=>$info);
	}


	// Output
	public static function printLog($type="all", $instance=null) // Output log
	{
		$combo_log=self::getComboLog();
	}

	public static function getComboLog()
	{
		$combo_log=self::$log;

		// Get all of the logs into one associative array
		foreach(self::$instances as $instance)
		{
			$instanceLog=$instance->getLog();
			$combo_log["Exception"] = array_merge($combo_log["Exception"], $instanceLog["Exception"]);
			$combo_log["Info"] = array_merge($combo_log["Info"], $instanceLog["Info"]);
			$combo_log["Depends"] = array_merge($combo_log["Depends"], $instanceLog["Depends"]);
		}

		return $combo_log;
	}

	public static function getFullLog()
	{
		$fullLog=array_merge($combo_log["Exception"], $combo_log["Info"]);
		$fullLog=array_merge($fullLog, $combo_log["Depends"]);

		$sortFunction=function($a, $b) {
			if($a["event_id"]==$b["event_id"])
				return 0;
			return ($a["event_id"]>$b["event_id"])?1:-1;
		};

		usort($fullLog, $sortFunction);

		return $fullLog;
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
		if(is_null(self::$instances))
			self::$instances=array();

		if(!array_key_exists($instanceName, self::$instances))
		{
			self::$instances[$instanceName]=new SimpleDebugInstance(self::$settings);
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

}

class SimpleDebugInstance
{
	// Instance Configurations
	// Current debug mode
	private $mode=0;

	// Keep track of problems so that there's one point of contact for components to know what to do
	private $errorLevel=0;


	// Log output format
	private $format="Dbg (Module: {MOD}): {LINENUM} {MESSAGE} (Error Level: {ERRLVL})";

	private $log=array("Info" => array(), "Depends" => array(), "Exception" => array());

	// Time output format
	private $time_format="";

	// Path to log file
	private $file_path="";

	public function __construct($settings)
	{
		$this->changeSettings($settings);
	}

	public function changeSettings($settings)
	{
		foreach($settings as $conf=>$val)
			$this->$conf=$val;
	}

	// Retrieving $this->log
	public function printLog()
	{
	}
	public function getLog()
	{
		return $this->log;
	}

	// Log functions for different types
	public function logException($e)
	{
		$this->logEvent("Exception", $e);
	}
	public function logInfo($info)
	{
		$this->logEvent("Info", $info);
	}
	public function logDepends($depends)
	{
		$this->logEvent("Depends", $depends);
	}

	// Function for all log functions to go through when interacting with $this->log
	public function logEvent($type, $info)
	{
		$this->log[$type][]=array("event_id"=>SimpleDebug::$event_id++, "type"=>$type, "time"=>time(), "message"=>$info);
	}
}
?>
