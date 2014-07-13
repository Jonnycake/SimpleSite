<?php
/*
 *    SimpleSite v1.0: Create an extendable website.
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
	error_reporting(E_ALL);
	session_start();
	if(@($_SESSION['is_admin']!=1))
	{
		$_GET['debug']=0;
	}
	if(@($_GET['debug'])==1)
		echo "Dbg: Start".time()."\n";
	define('SIMPLESITE',1);
	include("include.php");
	$ssite=new $configs['default_controller']($configs);
	if(@($_GET['debug'])==1)
		echo "Dbg: End".time()."\n";
?>
