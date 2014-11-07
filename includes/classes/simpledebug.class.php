<?php
/*
 *    SimpleDebug 1.0: Basic debugging/logging functions.
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
 * Output dumps throughout runtime
 */
define('SDBG_TRACERUN', 8);

/**
 * Output backtraces throughout runtime
 */
define('SDBG_DUMPRUN', 16);

/**
 * Output both stack traces and selfDumps throughout runtime
 */
define('SDBG_LOUDRUN', 24);

/**
 * Combine SDBG_LOUDRUN with SDBG_ALL
 */
define('SDBG_SUPERLOUD', 31);


/**
 * SimpleDebug class
 *
 * @version 1.0
 * @todo Handle dependencies
 * @todo Root-Cause Analysis
 * @todo Assertions
 * @todo error_get_last() compatability
 * @todo Error levels
 * @todo SimpleDebug class log to instance
 * @todo Update SimpleDebugInstance class
 */
class SimpleDebug
{
	// Literals
	/**
	 * Number of eventsw that have been recorded so far
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

	/**
	 * Array of dependencies
	 *
	 * @var array $depends
	 */
	private static $depends=null;

	/**
	 * Array of dependency relationships
	 *
	 * @var array $dependRel
	 */
	private static $dependRel=null;

	/**
	 * Array of missing dependencies
	 *
	 * @var array $missingDepends
	 */
	private static $missingDepends=null;

	/**
	 * Array of exceptions
	 *
	 * @var array $exceptionErrors
	 */
	private static $exceptionErrors=null;

	/**
	 * Array of dependency levels
	 *
	 * @var array $dependLevels
	 */
	private static $dependLevels=null;

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

		//self::printLog();
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

		$error=error_get_last();
		$wasFatal=false;
		$handlerCalled=false;
		$handleEID=null;
		$errorEID=null;
		$handler=self::$settings['fatalHandler'];
		try
		{
			switch($error['type'])
			{
				case E_ERROR:
					$errorEID=self::logException(new Exception("Fatal Error: ".json_encode($error)));
					if(is_string($handler))
					{
						$handleEID=self::logInfo("Attempting to handle with ".self::$settings['fatalHandler']);
						self::saveLog();
						$handlerCalled=true;
						self::printLog();
						$handler();
					}
					$wasFatal=true;
					break;
				case E_PARSE:
					$errorEID=self::logException(new Exception("Parse Error: ".json_encode($error)));
					unset($_GET['mod']);
					if(is_string($handler))
					{
						$handleEID=self::logInfo("Attempting to handle with ".self::$settings['fatalHandler']);
						self::saveLog();
						$handlerCalled=true;
						self::printLog(); // Print in case $handler errors out
						$handler();
					}
					$wasFatal=true;
					break;
				default:
					break;
			}
		}
		catch(Exception $e)
		{
			self::logInfo("Could not recover from fatal error - Event #${errorEID}");
		}

		if($handlerCalled)
		{
			self::logInfo("Lines #0 through #${handleEID} are repeated from a previous log.");
		}
		self::saveLog();


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
		self::initLog();
		self::initSettings();
		self::$exceptionErrors[]=$e;
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
		return self::logEvent("Exception", $info);
	}

	/**
	 * Log an info only message
	 *
	 * @param string $info The text to be logged
	 * @return void
	 */
	public static function logInfo($info)
	{
		return self::logEvent("Info", $info);
	}

	/**
	 * Log a missing dependency error
	 *
	 * @param string $depends The name of the dependency that is missing.
	 * @return void
	 */
	public static function logDepends($depends)
	{
		self::initDepends();
		$event_id=null;
		if(is_array($depends))
		{
			$event_id=self::logEvent("Depends", "Missing ${depends['name']}: ${depends['description']}");
		}
		else if(is_string($depends))
		{
			$event_id=self::logEvent("Depends", $depends);
			$depends==array("name"=>$depends, "description"=>"Unknown information.");
		}
		else
		{
			$event_id=self::logException(new Exception("Bad depends passed to logDepends."));
		}

		// Make sure it's not a duplicate
		$duplicate=false;
		foreach(self::$missingDepends as $missingDepend)
		{
			if($missingDepend['name']==$depends['name'])
			{
				$duplicate=true;
			}
		}

		if(!$duplicate)
		{
			self::$missingDepends[]=$depends;
		}
		return $event_id;
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
	public static function logEvent($type, $info, $errorLevel=0)
	{
		self::initLog();

		if(self::$settings['loud'] & SDBG_TRACERUN)
		{
			echo "Outputting self::trace() -\n";
			var_dump(self::trace());
		}

		if(self::$settings['loud'] & SDBG_DUMPRUN)
		{
			echo "Outputting self::dumpSelf() -\n";
			var_dump(self::dumpSelf());
		}


		if(!isset(self::$log[$type]))
			self::$log[$type]=array();
		self::$log[$type][]=array("event_id"=>self::$event_id++, "type"=>$type, "time"=>time(), "message"=>$info);

		return (self::$event_id-1);
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
	 * Get the full array of logs
	 *
	 * Returns in a one dimensional, unsorted, array.
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

		return $fullLog;
	}

	/**
	 * Save the log to the output file
	 *
	 * File path is determined by the $settings array
	 *
	 * @return void
	 */
	public static function saveLog()
	{
		self::initLog();
		self::initSettings();
		if(self::$settings['savelog'])
		{
			$logOutput=self::formatLog(self::getFullLog());
			if(self::$settings['save_striptags'])
			{
				$logOutput=strip_tags($logOutput);
			}
			file_put_contents(self::$settings['logfile'], "${logOutput}\n", FILE_APPEND);
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
		if(is_null(self::$exceptionErrors))
			self::$exceptionErrors=array();
		self::initDepends();
	}

	/**
	 * Iniitalize the depends array
	 *
	 * @return void
	 */
	public static function initDepends()
	{
		if(is_null(self::$depends))
			self::$depends=array( "hard" => array(), "soft" => array() );
		if(is_null(self::$dependRel))
			self::$dependRel=array();
		if(is_null(self::$missingDepends))
			self::$missingDepends=array();
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
						"fatalHandler"  => null,
						"save_striptags"     => false
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

		$x=0;
		foreach($logs as $log)
		{
			$formattedLog.="${format}\n";
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
	 * @param bool $filtered Whether or not to filter the logs by certain types
	 * @param bool $force Whether or not to force output even when loud is down
	 * @return void
	 */
	public static function printLog($instance=null, $filtered=true, $force=false)
	{
		self::initSettings();
		if((self::$settings['loud']>SDBG_QUIET) || $force)
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
				}
			}
			else
			{
				$full_log=self::getInstanceLog($instance);
			}
			echo self::formatLog($full_log);
		}
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

		$filteredLog=array();
		if((self::$settings['loud'] & SDBG_INFO))
		{
			$filteredLog["Info"]=$comboLog["Info"];
		}
		if((self::$settings['loud'] & SDBG_DEPEND))
		{
			$filteredLog["Depends"]=$comboLog["Depends"];
		}
		if((self::$settings['loud'] & SDBG_EXCEPT))
		{
			$filteredLog["Exception"]=$comboLog["Exception"];
		}

		return $filteredLog;
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

	/**
	 * Log a Dump of self
	 *
	 * @return array The array returned from get_class_vars()
	 */
	public static function dumpSelf()
	{
		return get_class_vars("SimpleDebug");
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

	/**
	 * Register Dependencies
	 *
	 * @param array $dependency Associative array containing basic information about the dependency.
	 * @param callable|null $checkFunc Function to check if the dependency exists, otherwise it is assumed to use a constant
	 * @param array $parentDepends The names of the dependencies that this dependency is dependent on
	 * @param bool $hard Whether or not it's a "hard" dependency or not
	 * @return void
	 */
	public static function regDepend($dependency, $avoidFunc=null, $checkFunc=null, $parentDepends=array(), $hard=true)
	{
		self::initDepends();
		$dependency['avoidFunc']=create_function("",$avoidFunc);
		if(!is_null($checkFunc))
			self::$depends[($hard)?"hard":"soft"][$dependency["name"]]=array(create_function("", $checkFunc), $dependency);
		else
			self::$depends[($hard)?"hard":"soft"][$dependency["name"]]=array(null, $dependency);

		if(!isset(self::$dependRel[$dependency['name']]))
			self::$dependRel[$dependency['name']]=array();

		foreach($parentDepends as $pDepend)
		{
			self::$dependRel[$dependency['name']][]=$pDepend;
		}
	}

	/**
	 * Check dependencies
	 *
	 * @param string|null
	 * @return bool If there were missing dependencies
	 */
	public static function checkDepend($dependency=null, $log=false)
	{
		self::initDepends();

		$errors=array();

		if(is_null($dependency))
		{
			foreach(self::$depends['hard'] as $depend)
			{
				if(!self::checkDepend($depend[1]['name']))
				{
					// TODO - Handle as fatal error
					$errors[]=$depend[1];
					self::logDepends($depend[1]);
					if(isset($depend[1]['avoidFunc']))
					{
						self::logInfo("Attempting to avoid dependency: ".$depend[1]['name']." by using the avoidFunc...");
						$depend[1]['avoidFunc']();
					}
				}
			}
			foreach(self::$depends['soft'] as $depend)
			{
				// TODO - Avoid dependency
				if(!self::checkDepend($depend[1]['name']))
				{
					$errors[]=$depend[1];
					self::logDepends($depend[1]);
				}
			}
			return (count($errors)>0);
		}
		else if(is_string($dependency))
		{
			if(array_key_exists($dependency, self::$depends['hard']))
			{
				$depend=self::$depends['hard'][$dependency];
				$hard=true;
			}
			else if(array_key_exists($dependency, self::$depends['soft']))
			{
				$depend=self::$depends['soft'][$dependency];
				$hard=false;
			}
			else
			{
				$depend=array( null, array( "name"=>$dependency, "description"=>"Assumed constant...", "fix"=>null ));
				$hard=false;
			}

			if(!is_null($depend[0]))
			{
				if(!($depend[0]()))
				{
					// TODO - Handle soft vs hard
					$errors[]=$depend[1];
				}
			}
			else
			{
				if(!isset($depend[1]['name']))
				{
					self::logException(new Exception("Depend array isn't correctly formed."));
					$errors[]=$depend[1];
				}
				else
				{
					// TODO - Handle as hard
					if(!defined($depend[1]['name']))
						$errors[]=$depend[1];
				}
			}
		}
		else
		{
			self::logException(new Exception("Bad dependency name"));
		}
		$isError=false;
		foreach($errors as $error)
		{
			$isError=true;
			if($log)
			{
				self::logDepends($error);
			}
		}

		return $isError;
	}

	/**
	 * Check Relationships
	 */

	/**
	 * Retrieve list of missing dependencies
	 */
	public static function getDependErrors()
	{
		self::initDepends();

		return self::$missingDepends;
	}

	/**
	 * Find the root cause of an error
	 */
	public static function rootCauseFinder()
	{
		if(self::checkDepend())
		{
			foreach(self::$missingDepends as $depend)
			{
				echo $depend['name'];
			}
		}
		// Check the latest error
			// Based on error and dependency type figure out if any of the missing dependencies caused the problem
				// If so then the highest-level relevant missing dependency is the root cause
					// Attempt to install the dependency (if $depends['fix'] is provided)
				// Else
					// Dependency is not the root cause
		// Check through assertions in a reverse order
			// If any assertions fail
				// Check through exceptions which occurred AFTER the assertion
					// Based on exception type, we can determine if the assertion led to the exception
						// If so then that assertion is POSSIBLY the root cause
							// If that assertion is dependent upon any previous assertions
								// Check those and find the highest-level relevant assertion which failed
						// Else continue backtracking
			// If no assertions fail
				// If the last exception occurred right before the fatal error
					// That exception must be the root cause
		// If it gets here then no root cause can be automatically identified
	}
}

/**
 * Class for debug instances to use
 *
 * @todo Update to be more consistent with SimpleDebug, as little could should be rewritten as possible
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
	 *
	 * @todo Something similar to interact with SimpleDebug::logDepends()
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
