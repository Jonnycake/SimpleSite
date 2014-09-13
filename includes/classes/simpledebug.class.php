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

if(SIMPLESITE!=1)
	die("Can't access this file directly.");

class SimpleDebug
{
	// Should be able to use an instance of this class as a property of a SimpleSite
	// Should be able to set the file path as well as the file name and any prefix/suffixes
	// Should be able to change log message format (formatted based on variables)

	// Current debug mode
	private $mode=0;

	// Keep track of problems so that there's one point of contact for components to know what to do
	private $errorLevel=0;

	// Log output format
	private $format="Dbg (Module: {MOD}): {LINENUM} {MESSAGE} (Error Level: {ERRLVL})";

	// Time output format
	private $time_format=null;

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
	public static function logException($e)
	{
	}

	public static function logInfo($info)
	{
	}

	public static function logDepends($dependency)
	{
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

}
?>
