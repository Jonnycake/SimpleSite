<?php
/*
 *    SimpleSite Index File v1.1: Create an extendable website.
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
 *
 *
 * Normal Debug Output (from testModule):
 * Dbg: Start
 * Dbg: __construct()
 * Dbg: loadModules()
 * Dbg: showsite("testModule")
 * Dbg: $obj->choosePage($configs)
 * Dbg: readtemplate("/var/www/templates/default/overall.template","testModule")
 * Dbg: parsetemplate($content,"testModule")
 * Dbg: $obj->isInstalled()
 * Dbg: $obj->getContent($configs)
 * Dbg: readtemplate("/var/www/templates/mods/testModule.template","testModule")
 * Dbg: parsetemplate($content,"testModule")
 * Dbg: $obj->isInstalled()
 * Dbg: readtemplate("/var/www/templates/default/header.template","")
 * Dbg: parsetemplate($content,"")
 * Dbg: readtemplate("/var/www/templates/default/footer.template","")
 * Dbg: parsetemplate($content,"")
 */

/**
 * SimpleSite index page.
 *
 * Entry point for SimpleSite.
 *
 * @package     SimpleSite Core
 * @author      Jonathan Stockton <jonathan@simplesite.ddns.net>
 */


	/**
	 * Set error_reporting to E_ALL and start the session
	 */
	error_reporting(E_ALL);
	session_start();

	/**
	 * Define SIMPLESITE constants as some includes can't be opened directly.
	 */
	define('SIMPLESITE',1);

	/**
	 * Include the include file
	 *
	 * @include include.php
	 */
	include("include.php");

	/**
	 * Set up the debugging
	 */
	if(@($_SESSION['is_admin']!=1))
	{
		$_GET['debug']=0;
	}
	else if(isset($_SESSION['debug']))
		$_GET['debug']=$_SESSION['debug'];

	register_shutdown_function("SimpleDebug::shutdownFunction");
	SimpleDebug::setSetting("loud", $_GET['debug']);
	SimpleDebug::logInfo("Start");

	/**
	 * Start the site with the default controller.
	 */
	$ssite=new $configs['default_controller']($configs);
	SimpleDebug::logInfo("End");
?>
