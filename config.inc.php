<?php
/*
 *    SimpleSite Configuration File v2.1: Define necessary variables.
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
 * Set up all of the configurations
 *
 * @package SimpleSite Core
 * @author Jonathan Stockton <jonathan@simplesite.ddns.net>
 */

/**
 * File can not be accessed directly.
 */
if(!SIMPLESITE)
	die("Can't access this file directly.");

set_exception_handler("SimpleDebug::exceptionHandler"); // Allow logging of uncaught exceptions

// Initialize config array and super-global arrays
SimpleDebug::logInfo("Checking super-globals...");
if(!isset($_SERVER))
{
	SimpleDebug::logInfo("Initializing \$_SERVER.");
	global $_SERVER;
	$_SERVER=array();
	$_SERVER['DOCUMENT_ROOT']=__DIR__;
	$_SERVER['REMOTE_ADDR']="0.0.0.0";
}
if(!isset($_GET))
{
	SimpleDebug::logInfo("Initializing \$_GET.");
	global $_GET;
	$_GET=array();
}
if(!isset($_POST))
{
	SimpleDebug::logInfo("Initializing \$_POST.");
	global$_POST;
	$_POST=array();
}
if(!isset($_REQUEST))
{
	SimpleDebug::logInfo("Initializing \$_REQUEST.");
	global $_REQUEST;
	$_REQUEST=array_merge($_GET, $_POST);
}
if(!isset($_SESSION))
{
	SimpleDebug::logInfo("Initializing \$_SESSION.");
	$GLOBALS["_SESSION"]=array();
}
$configs=array();


/*********************************
 *    Display Configurations     *
 *    ----------------------     *
 * SHOULD NOT BE MANUALLY EDITED *
 *********************************/
SimpleDebug::logInfo("Setting display configurations.");
$configs["default_theme"]="JCake";
$configs["default_mod"]="";
$configs["default_controller"]="DefaultSite";



/***************************************************************************************************
 *                                     Path Configurations                                         *
 *                                     -------------------                                         *
 * $configs["path"]["root"]             - This should be changed to the path to SimpleSite         *
 *                                        on your site.                                            *
 *                                        http://www.site.com/SimpleSite/ would be "/SimpleSite/"  *
 *                                                                                                 *
 * $configs["path"]["themes"]           - This should be the path to SimpleSite themes             *
 *                                                                                                 *
 * $configs["path"]["mod_templates"]    - This is the path to the module template files            *
 *                                                                                                 *
 * $configs["path"]["custom_templates"] - This should be the path to the custom templates          *
 *                                        which are templates you want to use regardless of theme. *
 *                                                                                                 *
 * $configs["path"]["includes"]         - Normally will not need to be edited, but it is the       *
 *                                        directory you would like to load classes, etc. from      *
 *                                        useful for separating back-end development projects      *
 *                                                                                                 *
 * $configs["path"]["tmpdir"]           - Should not need to be edited, just where the             *
 *                                        temporary files are stored.                              *
 *                                                                                                 *
 * $configs["path"]["templates"]        - Don't edit unless you know what you're doing, previous   *
 *                                        configurations generate this value                       *
 *                                                                                                 *
 ***************************************************************************************************/
SimpleDebug::logInfo("Setting path configurations.");
$configs["path"]["root"]="/";
$configs["path"]["themes"]="templates/themes";
$configs["path"]["mod_templates"]="templates/mods";
$configs["path"]["custom_templates"]="templates/custom";
$configs["path"]["js_assets"] = $configs["path"]["root"] . "assets/js/";
$configs["path"]["css_assets"] = $configs["path"]["root"] . "assets/css/";
$configs["path"]["img_assets"] = $configs["path"]["root"] . "assets/images/";
$configs["path"]["contrib_assets"] = $configs["path"]["root"] . "assets/contrib/";
$configs["path"]["theme_assets"] = $configs["path"]["root"] . "assets/themes/";
$configs["path"]["mod_assets"] = $configs["path"]["root"] . "assets/mods/";
$configs["path"]["widget_assets"] = $configs["path"]["root"] . "assets/widgets/";

SimpleDebug::logInfo("Setting dynamically generated path configurations.");
// The following configurations should not be changed unless you know what you are doing as they are dynamically set
// Set up super-globals in case they aren't already defined
// Note, in the case of this being called through a symlink,
// the path it points to is what is defined here
// However since both situations are edge cases for now this works
if(!isset($_SERVER['DOCUMENT_ROOT']))
{
	$dirArr=array();
	$tmpDirArr=explode($configs['path']['root'], __DIR__."/");
	$tmpArrCnt=count($tmpDirArr);
	$curSpot=0;

	if($tmpArrCnt>1)
	{
		foreach($tmpDirArr as $directory)
		{
			if(++$curSpot < $tmpArrCnt)
			{
				$dirArr[]=$directory;
			}
			else
				break;
		}
	}

	$_SERVER['DOCUMENT_ROOT']=implode($configs['path']['root'],$dirArr);
	SimpleDebug::logInfo("Setting DOCUMENT_ROOT to ${SERVER['DOCUMENT_ROOT']}.");
}
$configs["path"]["includes"]=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/";
$configs["path"]["tmpdir"]=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."/tmp/";
$configs["path"]["templates"]=$configs["path"]["themes"]."/".(@($_SESSION['selected_theme']=="")?$configs['default_theme']:$_SESSION['selected_theme']);
$configs["path"]["configs"]=__FILE__;



/****************************************************************************************************
 *                                      Database Configurations                                     *
 *                                      -----------------------                                     *
 * $configs["database"]["type"]         - This is the type of database you plan to use, may be:     *
 *                                                 MySQL                                            *
 *                                                 MSSQL                                            *
 *                                                 Oracle                                           *
 *                                                                                                  *
 * $configs["database"]["username"]     - The username you use to log into the database - if no DB  *
 *                                        is being used, this will be your login for the adminCP    *
 *                                                                                                  *
 * $configs["database"]["password"]     - The password you use to log into the database - if no DB  *
 *                                        is being used, this will be your password for the adminCP *
 *                                                                                                  *
 * $configs["database"]["database"]     - This is the database you want to use after connecting to  *
 *                                        the SQL server                                            *
 *                                                                                                  *
 * $configs["database"]["tbl_prefix"]   - The prefix to use in front of all default tables          *
 *                                                                                                  *
 ****************************************************************************************************/
SimpleDebug::logInfo("Setting database configs.");
$configs["database"]=array();
$configs["database"]["type"]="mysql";
$configs["database"]["host"]="127.0.0.1";
$configs["database"]["username"]="root";
$configs["database"]["password"]="";
$configs["database"]["database"]="simplesite";
$configs["database"]["tbl_prefix"]="SS_";

// Array of IP addresses blocked
$configs["blocked"]=array();
SimpleDebug::logInfo("Blocking ".count($configs['blocked'])." remote hosts.");

/*****************************************************************************************************
 *                                       Debugging Configurations                                    *
 *                                       ------------------------                                    *
 * $configs["debugging"]["loud"]              - This is the setting for SimpleDebug to automatically *
 *                                              start at.  You should leave it at 0 so that it can   *
 *                                              be filtered based on the user.                       *
 *                                                                                                   *
 * $configs["debugging"]["savelog"]           - Boolean value of whether to save the log at the end  *
 *                                              of execution.                                        *
 *                                                                                                   *
 * $configs["debugging"]["logfile"]           - Should not need to be changed, put into tmp dir (in  *
 *                                              path configs) under name SimpleDebug.log.            *
 *                                                                                                   *
 * $configs["debugging"]["errorLevel"]        - Current error level of the system, should be left at *
 *                                              0.                                                   *
 *                                                                                                   *
 * $configs["debugging"]["format"]            - Format that the debug output should be in.           *
 *                                                                                                   *
 * $configs["debugging"]["exception_fmt"]     - Format the message for exceptions should be in.      *
 *                                                                                                   *
 * $configs["debugging"]["time_format"]       - Format that the date/time output should be in.       *
 *****************************************************************************************************/
SimpleDebug::logInfo("Setting debugging configs.");
$configs["debugging"]["loud"]=0;
$configs["debugging"]["savelog"]=true;
$configs["debugging"]["logfile"]=$configs["path"]["tmpdir"]."SimpleDebug.log";
$configs["debugging"]["errorLevel"]=0;
$configs["debugging"]["format"]="Dbg: {TYPE}: #{ID} ({TIME}): {MESSAGE}";
$configs["debugging"]["exception_fmt"]="{MESSAGE} in {FILE} on line {LINE} - backtrace JSON: {BACKTRACE}";
$configs["debugging"]["time_format"]="m/d/Y H:i:s";
SimpleDebug::setSettings($configs['debugging'], true); // Put these settings and propogate to any existing instances


$loadDisabled=false; // Whether the autoloader should look at disabled modules or not
?>
