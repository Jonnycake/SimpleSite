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

/**
 * Helper class for debugging
 *
 * @package SimpleDebug
 * @author Jonathan Stockton <jonathan@simplesite.ddns.net>
 */

/**
 * SimpleDebug class
 */
class SimpleDebug
{
	// Literals
	/**
	 * Number of events that have been recorded so far
	 *
	 * @var int $event_id
	 */
	public static $event_id=0;

	// Arrays
	/**
	 * Array of events
	 *
	 * @var array $log
	 */
	public static $log=null;

	/**
	 * Array of configuration settings
	 *
	 * @var array $settings
	 */
	private static $settings=null;

	// Configuration Functions
	/**
	 * Get the array of configuration settings
	 *
	 * @return array The array of configuration settings
	 */
	public static function getSettings()
	{
		self::initSettings();
		return self::$settings;
	}

	/**
	 * Change multiple configuration settings
	 *
	 * @param array $settings The array of settings to replace, example: array("loud" => 0)
	 * @param bool $propogate Whether or not to propogate the changes to debug instances
	 * @return void
	 */
	public static function setSettings($settings, $propogate=false)
	{
		self::initSettings();
		foreach($settings as $setting=>$value)
		{
			self::$settings[$setting]=$value;
		}
		if($propogate)
			self::propogateSettings();
	}

	/**
	 * Retrieve a single setting
	 *
	 * @param string $setting The name of the setting you wish to retrieve
	 * @return mixed The configuration value for $setting
	 */
	public static function getSetting($setting)
	{
		self::initSettings();
		return self::$settings[$setting];
	}

	/**
	 * Set a single setting
	 *
	 * @param string $setting The name of the setting you want to change
	 * @param mixed $value The value you want to change the setting to
	 * @param bool $propogate Whether or not to propogate the change to debug instances
	 * @return void
	 */
	public static function setSetting($setting, $value, $propogate=false)
	{
		self::initSettings();
		self::$settings[$setting]=$value;
		if($propogate)
			self::propogateSettings();
	}

	/**
	 * Propogate the settings to debug instances
	 *
	 * @return void
	 */
	public static function propogateSettings()
	{
		self::initSettings();
		foreach(self::$instances as $instance)
		{
			$instance->changeSettings(self::$settings);
		}
	}

	// Log functions
	/**
	 * Executed when an exception is not handled.
	 *
	 * @param Exception $e The exception to be handled.
	 * @return void
	 */
	public static function exceptionHandler($e)
	{
		self::initSettings();

		self::logException($e);

		if(self::$settings['loud']>0)
			self::printLog();
	}

	/**
	 * Executed at the end of the run
	 *
	 * Saves and outputs log
	 *
	 * @return void
	 */
	public static function shutdownFunction()
	{
		self::initSettings();
		self::initLog();
		self::saveLog();

		if(self::$settings['loud']>0)
			self::printLog();
	}

	/**
	 * Log a handled exception
	 *
	 * @param Exception $e The exception that was handled.
	 * @return void
	 */
	public static function logException($e)
	{
		self::initSettings();
		self::$settings['errorLevel']++;
		$line_number=$e->getLine();
		$file=$e->getFile();
		$message=$e->getMessage();
		$backtrace=json_encode(debug_backtrace());
		$info=self::$settings['exception_fmt'];
		$info=str_replace("{FILE}", $file, $info);
		$info=str_replace("{LINE}", $line_number, $info);
		$info=str_replace("{MESSAGE}", $message, $info);
		$info=str_replace("{BACKTRACE}", $backtrace, $info);
		self::logEvent("Exception", $info);
	}

	/**
	 * Log an info only message
	 *
	 * @param string $info The text to be logged
	 * @return void
	 */
	public static function logInfo($info)
	{
		self::logEvent("Info", $info);
	}

	/**
	 * Log a missing dependency error
	 *
	 * @param string $depends The name of the dependency that is missing.
	 * @return void
	 */
	public static function logDepends($depends)
	{
		self::logEvent("Depends", $depends);
	}

	/**
	 * Log an event
	 *
	 * Used by all other logging functions.
	 *
	 * @param string $type The type of event to log (Info, Exception, or Depends)
	 * @param string $info The text to use to log the event
	 */
	public static function logEvent($type, $info)
	{
		self::initLog();
		self::$log[$type][]=array("event_id"=>self::$event_id++, "type"=>$type, "time"=>time(), "message"=>$info);
	}

	/**
	 * Retrieve the log
	 *
	 * @param string $instance The instance to retrieve the log from.
	 * @return array The log from the instance or the overall SimpleDebug class
	 */
	public static function getLog($instance=null)
	{
		if(is_null($instance))
			return self::$log;
		else
		{
			return self::getInstanceLog($instance);
		}
	}

	/**
	 * Retrieve the log from an instance
	 *
	 * @param mixed $instance The name of the instance to retrieve the log from (null means all, can be array or a string)
	 * @return array The array of logs
	 */
	public static function getInstanceLog($instance=null)
	{
		self::initInstances();
		$instanceLog=array();
		if(is_null($instance))
		{
			foreach(self::$instances as $instanceName=>$instance)
			{
				$instanceLog[$instanceName]=$instance->getLog();
			}
		}
		else if(is_array($instance))
		{
			foreach($instance as $instanceName)
			{
				$instanceLog[$instanceName]=self::$instances[$instanceName]->getLog();
			}
		}
		else if(isset(self::$instances[$instance]))
		{
			$instanceLog=array(self::$instances[$instance]->getLog());
		}
		else
			return null;

		return $instanceLog;
	}

	/**
	 * Get the combo log
	 *
	 * Retrieves the array of logs of one or more instances combined with the log from SimpleDebug itself
	 *
	 * @param mixed $instances The instances to retrieve logs from
	 * @param array $instanceLogs Array of logs to add into the logs.
	 * @return array Log containing all the $instances logs, $instancelogs, and SimpleDebug's logs
	 */
	public static function getComboLog($instances=null, $instanceLogs=null)
	{
		self::initLog();
		$combo_log=self::$log;

		// Get all of the logs into one associative array
		if(is_null($instanceLogs))
		{
			$instanceLogs=self::getInstanceLog($instances);
			if(is_null($instanceLogs))
				$instanceLogs=array();
		}

		foreach($instanceLogs as $instanceLog)
		{
			$combo_log["Exception"] = array_merge($combo_log["Exception"], $instanceLog["Exception"]);
			$combo_log["Info"] = array_merge($combo_log["Info"], $instanceLog["Info"]);
			$combo_log["Depends"] = array_merge($combo_log["Depends"], $instanceLog["Depends"]);
		}

		return $combo_log;
	}

	/**
	 * Get the full (unseparated by type) array of logs
	 *
	 * @param mixed $instances 
	 */
	public static function getFullLog($instances=null, $combo_log=null)
	{
		if(is_null($combo_log))
			$combo_log=self::getComboLog($instances);

		$fullLog=array_merge($combo_log["Exception"], $combo_log["Info"]);
		$fullLog=array_merge($fullLog, $combo_log["Depends"]);

		return $fullLog;
	}
	public static function saveLog() // Save log to log file
	{
		self::initSettings();
		if(self::$settings['savelog'])
		{
			file_put_contents(self::$settings['logfile'], self::formatLog(self::getFullLog())."\n", FILE_APPEND);
		}
	}

	// Initialization functions
	public static function initLog()
	{
		if(is_null(self::$log))
			self::$log=array( "Exception"=>array(), "Info"=>array(), "Depends"=>array() );
	}
	public static function initSettings()
	{
		if(is_null(self::$settings))
			self::$settings=array(
						"loud"          => 0,
						"savelog"       => false,
						"logfile"       => "SimpleDebug.log",
						"errorLevel"    => 0,
						"format"        => "Dbg: {TYPE}: #{ID} ({TIME}): {MESSAGE}",
						"exception_fmt" => "{MESSAGE} in {FILE} on line {LINE} - backtrace JSON: {BACKTRACE}",
						"time_format"   => "m/d/Y H:i:s",
					);
	}
	public static function initInstances()
	{
		if(is_null(self::$instances))
			self::$instances=array();
	}

	// Output/Misc.
	public static function formatLog($logs, $instance=null, $format=null)
	{
		self::initSettings();

		// Sort logs by event_id (order they happened in)
		$sortFunction=function($a, $b) {
			if($a["event_id"]==$b["event_id"])
				return 0;
			return ($a["event_id"]>$b["event_id"])?1:-1;
		};

		usort($logs, $sortFunction);

		$formattedLog="";
		if(!is_null($instance) && is_string($instance))
		{
			$format=self::$instances[$instance]->format;
		}
		else if(is_null($format))
			$format=self::$settings['format'];

		foreach($logs as $log)
		{
			$formattedLog.=$format."\n";
			$formattedLog=str_replace("{ID}", $log['event_id'], $formattedLog);
			$formattedLog=str_replace("{TIME}", date(self::$settings['time_format'], $log['time']), $formattedLog);
			$formattedLog=str_replace("{MESSAGE}", $log['message'], $formattedLog);
			$formattedLog=str_replace("{TYPE}", $log['type'], $formattedLog);
		}
		return $formattedLog;
	}
	public static function printLog($instance=null, $type="all")
	{
		$full_log=array();
		if(is_null($instance))
		{
			if($type=="all")
			{
				$full_log=self::getFullLog();
			}
			else
			{
				$combo_log=self::getComboLog();
				if(isset($combo_log[$type]))
				{
					foreach($combo_log[$type] as $log)
					{
						$full_log[]=$log;
					}
				}
			}
		}
		else
		{
			$full_log=self::getInstanceLog($instance);
		}

		echo self::formatLog($full_log);
	}
	public static function stacktrace()
	{
		$backtrace=debug_backtrace();
		array_shift($backtrace);
		return $backtrace;
	}


	/*
	 * This section should be used for code related to using the class in a non-static
	 * way so that modules, etc. can create their own logs without affecting other
	 * componets.
	 */

	// Array of named-instances created/retrieved/destroyed using the functions below
	private static $instances=null;

	public static function createInstance($instanceName)
	{
		self::initSettings();
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
	private $loud=0;

	// Keep track of problems so that there's one point of contact for components to know what to do
	private $errorLevel=0;


	// Log output format
	private $format="Dbg (Module: {MOD}): {LINENUM} {MESSAGE} (Error Level: {ERRLVL})";

	// Format for logged exceptions
	private $exception_fmt="{MESSAGE} in {FILE} on line {LINE} - backtrace JSON: {BACKTRACE}";

	// Log for instance logs only
	private $log=array("Info" => array(), "Depends" => array(), "Exception" => array());

	// Time output format
	private $time_format="";

	// Path to log file
	private $logfile="";

	// Whether or not to write to log file
	private $savelog=false;

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
		echo SimpleDebug::formatLog(SimpleDebug::getFullLog(null, $this->log));
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
