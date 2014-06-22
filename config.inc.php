<?php
/*
 *    SimpleSite Configuration File v2.0: Define necessary variables.
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
if(!SIMPLESITE)
	die("Can't access this file directly.");
$configs=array();

/*********************************
 *    Display Configurations     *
 *    ----------------------     *
 * SHOULD NOT BE MANUALLY EDITED *
 *********************************/
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
 * $configs["database"]["type"]         - This is the type of database you plan to use, may be:    *
 *                                                 MySQL                                           *
 *                                                 MSSQL                                           *
 *                                                 Oracle                                          *
 *                                                                                                 *
 * $configs["database"]["username"]     - The username you use to log into the database - if no DB *
 *                                        is being used, this will be your login for the adminCP   *
 *                                                                                                 *
 * $configs["database"]["password"]     - The password you use to log into the database - if no DB *
 *                                        is being used, this will be your password for the adminCP*
 *                                                                                                 *
 * $configs["database"]["database"]     - This is the database you want to use after connecting to *
 *                                        the SQL server                                           *
 *                                                                                                 *
 * $configs["database"]["tbl_prefix"]   - The prefix to use in front of all default tables         *
 *                                                                                                 *
 * $configs["debug"]["type"]            - The debugging mode to use:                               *
 *                                           site            - Debug for the entire site           *
 *                                           module_modName  - Only debug for "modName"            *
 *                                                                                                 *
 * $configs["debug"]["level"]           - The level of debugging to be used:                       *
 *                                           0 - No debugging                                      *
 *                                           1 - Debug only when $_GET['debug'] is set to 1        *
 *                                           2 - Always output debug messages                      *
 ***************************************************************************************************/
$configs["path"]["root"]="/SimpleSite/";
$configs["path"]["themes"]="templates/themes";
$configs["path"]["mod_templates"]="templates/mods";
$configs["path"]["custom_templates"]="templates/custom";
$configs["path"]["includes"]=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/";
$configs["path"]["tmpdir"]=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."/tmp/";
$configs["path"]["templates"]=$configs["path"]["themes"]."/".(@($_SESSION['selected_theme']=="")?$configs['default_theme']:$_SESSION['selected_theme']);

// Database Configurations
if(@($_GET['debug']==1))
	echo "Dbg: Database Configs\n";
$configs["database"]=array();
$configs["database"]["type"]="mysql";
$configs["database"]["host"]="127.0.0.1";
$configs["database"]["username"]="root";
$configs["database"]["password"]="";
$configs["database"]["database"]="simplesite";
$configs["database"]["tbl_prefix"]="SS_";

// Blocking 
$configs["blocked"]=array();

// Debugging
$configs["debug"]["type"]="site";
$configs["debug"]["level"]=0;
?>
