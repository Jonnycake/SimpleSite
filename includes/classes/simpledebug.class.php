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

// Debug Mode Constants
/**
 * No output
 */
define('SDBG_QUIET',0);

/**
 * Only informational messages
 */
define('SDBG_INFO',1);

/**
 * Only dependency errors
 */
define('SDBG_DEPEND',2);

/**
 * Only exceptions
 */
define('SDBG_EXCEPT',4);

/**
 * All debug output
 */
define('SDBG_ALL',7);

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
		self::initInstances();
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
		{
			self::printLog();
		}
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
		$backtrace=self::trace();
		$backtrace=json_encode($backtrace);
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
	 * @return void
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
	 * @param mixed $instances The instances to include in the logs (null means all)
	 * @param array $combo_log A combo log that should be included in the log
	 * @return array The full log
	 */
	public static function getFullLog($instances=null, $combo_log=null)
	{
		if(is_null($combo_log))
			$combo_log=self::getComboLog($instances);

		$fullLog=array();
		foreach($combo_log as $logType=>$logs)
		{
			$fullLog=array_merge($fullLog, $logs);
		}
		/*if(isset($combo_log["Exception"]))
		{
			$fullLog=$combo_log["Exception"];
		}
		
		$fullLog=array_merge($combo_log["Exception"], $combo_log["Info"]);
		$fullLog=array_merge($fullLog, $combo_log["Depends"]);*/

		return $fullLog;
	}

	/**
	 * Save the log to the output file
	 *
	 * @return void
	 */
	public static function saveLog() // Save log to log file
	{
		self::initLog();
		self::initSettings();
		if(self::$settings['savelog'])
		{
			file_put_contents(self::$settings['logfile'], self::formatLog(self::getFullLog())."\n", FILE_APPEND);
		}
	}

	// Initialization functions
	/**
	 * Initialize the log array
	 *
	 * @return void
	 */
	public static function initLog()
	{
		if(is_null(self::$log))
			self::$log=array( "Exception"=>array(), "Info"=>array(), "Depends"=>array() );
	}

	/**
	 * Initialize the settings array
	 *
	 * @return void
	 */
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

	/**
	 * Initialize the instances array
	 *
	 * @return void
	 */
	public static function initInstances()
	{
		if(is_null(self::$instances))
			self::$instances=array();
	}

	// Output/Misc.
	/**
	 * Put the logs into the proper format
	 *
	 * @param array $logs The array of logs (from getFulllog)
	 * @param mixed $instance The instances to use for formatting
	 * @param string $format The format to use for logs
	 * @return string The formatted version of the logs
	 */
	public static function formatLog($logs, $instance=null, $format=null)
	{
		self::initSettings();
		self::initInstances();

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

	/**
	 * Print the logs in the proper format
	 *
	 * @param string $instance The name of the instance to use the logs/format from
	 * @param string $type The type of events to output the log of
	 * @return void
	 */
	public static function printLog($instance=null, $filtered=false)
	{
		$full_log=array();
		if(is_null($instance))
		{
			if(!$filtered)
			{
				$full_log=self::getFullLog();
			}
			else
			{
				$combo_log=self::filterLog(self::getComboLog());
				$full_log=self::getFullLog(null, $combo_log);
				/*if(isset($combo_log[$type]))
				{
					foreach($combo_log[$type] as $log)
					{
						$full_log[]=$log;
					}
				}*/
			}
		}
		else
		{
			$full_log=self::getInstanceLog($instance);
		}

		echo self::formatLog($full_log);
	}

	/**
	 * Filter log array based on loud level
	 *
	 * @param array $comboLog The array of logs
	 * @return array The filtered array of logs
	 */
	public static function filterLog($comboLog, $mode=null)
	{
		self::initSettings();

		if(is_null($mode))
		{
			$mode=self::$settings['loud'];
		}

		switch(self::$settings['loud'])
		{
			case SDBG_QUIET:
				return array();
			case SDBG_INFO:
				unset($comboLog["Depends"]);
				unset($comboLog["Exception"]);
				break;
			case SDBG_DEPEND:
				unset($comboLog["Info"]);
				unset($comboLog["Exception"]);
				break;
			case (SDBG_INFO | SDBG_DEPEND):
				unset($comboLog["Exception"]);
				break;
			case (SDBG_EXCEPT | SDBG_INFO):
				unset($comboLog["Depends"]);
				break;
			case (SDBG_EXCEPT | SDBG_DEPEND):
				unset($comboLog["Info"]);
				break;
			case SDBG_EXCEPT:
				unset($comboLog["Depends"]);
				unset($comboLog["Info"]);
				break;
			case SDBG_ALL:
				break;
		}
		return $comboLog;
	}
	/**
	 * Generate a stack trace
	 *
	 * @return array The backtrace array
	 */
	public static function trace()
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
	/**
	 * Array of named-instances
	 *
	 * @var array $instances
	 */
	private static $instances=null;

	/**
	 * Create a new instance (if name is unique)
	 *
	 * @param string $instanceName What to name the instance
	 * @return SimpleDebugInstance Instance that was just created
	 */
	public static function createInstance($instanceName)
	{
		self::initSettings();
		self::initInstances();

		if(!array_key_exists($instanceName, self::$instances))
		{
			self::$instances[$instanceName]=new SimpleDebugInstance(self::$settings);
		}
		return self::$instances[$instanceName];
	}

	/**
	 * Destroy an instance
	 *
	 * @param string $instanceName The name of the instance
	 * @return void
	 */
	public static function destroyInstance($instanceName)
	{
		self::initInstances();
		unset(self::$instances[$instanceName]);
	}

	/**
	 * Retrieve an instance or create it if it doesn't exist
	 *
	 * @param string $instanceName The name of the instance to be retrieved
	 * @return SimpleDebugInstance Instance that was requested
	 */
	public static function getInstance($instanceName)
	{
		self::initInstances();
		if(isset(self::$instances[$instanceName]))
			return self::$instances[$instanceName];
		else
			return self::createInstance($instanceName);
	}

}

/**
 * Class for debug instances to use
 */
class SimpleDebugInstance
{
	/**
	 * Current debug mode
	 *
	 * @var int $loud
	 */
	private $loud=0;

	/**
	 * Error level
	 *
	 * @var int $errorLevel
	 */
	private $errorLevel=0;

	/**
	 * Log output format
	 *
	 * @var string $format
	 */
	private $format="Dbg (Module: {MOD}): {LINENUM} {MESSAGE} (Error Level: {ERRLVL})";

	/**
	 * Format for logged exceptions
	 *
	 * @var string $exception_fmt
	 */
	private $exception_fmt="{MESSAGE} in {FILE} on line {LINE} - backtrace JSON: {BACKTRACE}";

	/**
	 * Log for instance logs only
	 *
	 * @var array $log
	 */
	private $log=array("Info" => array(), "Depends" => array(), "Exception" => array());

	/**
	 * Time output format
	 *
	 * @var string $time_format
	 */
	private $time_format="";

	/**
	 * Path to log file
	 *
	 * @var string $logfile
	 */
	private $logfile="";

	/**
	 * Whether or not to write to log file
	 *
	 * @var bool $savelog
	 */
	private $savelog=false;

	/**
	 * Just sets up the configurations based on $settings
	 *
	 * @param array $settings The configuration array from SimpleDebug
	 * @return void
	 */
	public function __construct($settings)
	{
		$this->changeSettings($settings);
	}

	/**
	 * Change the settings
	 *
	 * @param array $settings The configuration array from SimpleDebug
	 * @return void
	 */
	public function changeSettings($settings)
	{
		foreach($settings as $conf=>$val)
			$this->$conf=$val;
	}

	/**
	 * Print only this instance's log
	 * @return void
	 */
	public function printLog()
	{
		echo SimpleDebug::formatLog(SimpleDebug::getFullLog(null, $this->log));
	}

	/**
	 * Retrieve this instance's log
	 *
	 * @return array The log array
	 */
	public function getLog()
	{
		return $this->log;
	}

	/**
	 * Log an exception
	 *
	 * @param Exception $e The exception to be logged
	 * @return void
	 */
	public function logException($e)
	{
		$this->logEvent("Exception", $e);
	}

	/**
	 * Log an informational message
	 *
	 * @param string $info The message to be logged
	 * @return void
	 */
	public function logInfo($info)
	{
		$this->logEvent("Info", $info);
	}

	/**
	 * Log a dependency error
	 *
	 * @param string $depends The name of the dependency that is missing
	 * @return void
	 */
	public function logDepends($depends)
	{
		$this->logEvent("Depends", $depends);
	}

	/**
	 * Function for all log functions to go through when interacting with $this->log
	 *
	 * @param string $type The type of event to be logged
	 * @param string $info The message to use in the log
	 * @return void
	 */
	public function logEvent($type, $info)
	{
		$this->log[$type][]=array("event_id"=>SimpleDebug::$event_id++, "type"=>$type, "time"=>time(), "message"=>$info);
	}
}
?>
