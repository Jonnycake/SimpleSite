<?php
/*
 *    SimpleSite Configuration File v1.0: Define necessary variables.
 *    Copyright (C) 2012 Jon Stockton
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
$configs=array();

// General Configurations
if(@($_GET['debug']==1))
	echo "Dbg: \$configs[]\n";
$configs["default_theme"]="theBasics";
$configs["default_mod"]="";
$configs["path"]["root"]="/SimpleSite_v1.0/";
$configs["path"]["themes"]="templates/themes";
$configs["path"]["templates"]=$configs["path"]["themes"]."/".(@($_SESSION['selected_theme']=="")?$configs['default_theme']:$_SESSION['selected_theme']);
$configs["path"]["mod_templates"]="templates/mods";
$configs["path"]["tmpdir"]="/tmp/simplesite";

// Database Configurations
if(@($_GET['debug']==1))
	echo "Dbg: Database Configs\n";
$configs["database"]=array();
$configs["database"]["host"]="127.0.0.1";
$configs["database"]["username"]="username";
$configs["database"]["password"]="password";
$configs["database"]["database"]="database";
$configs["database"]["tbl_prefix"]="SS_";

// Permanently blocked IPs
if(@($_GET['debug']==1))
	echo "Dbg: Blocked IPs\n";
$configs["blocked"]=array();

// Load up the constants
if(@($_GET['debug']==1))
	echo "Dbg: Database Constants\n";
$conn=mysql_connect($configs["database"]["host"],$configs["database"]["username"],$configs["database"]["password"]);
mysql_select_db($configs["database"]["database"]);
$res=mysql_query("SELECT * FROM ".$configs["database"]["tbl_prefix"]."constants;",$conn);
while(@($row=mysql_fetch_array($res)))
	define($row['name'],$row['value']);
mysql_close($conn);
?>
