<?php
/*
 *    SimpleSite Include File: Main include file for SimpleSite.
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
 */

/**
 * SimpleSite Include File
 *
 * Main include file for SimpleSite
 *
 * @package	SimpleSite Core
 * @author	Jonathan Stockton <jonathan@simplesite.ddns.net>
 */

/**
 * SIMPLESITE Constant - Included files can't be accessed directly.
 */
define('SIMPLESITE',1);

/**
 * SimpleDebug class for debugging.
 *
 * @include includes/classes/simpledebug.class.php
 */
include("includes/classes/SimpleDebug.php");

/**
 * PHP file setting up all of the configurations.
 *
 * @include config.inc.php
 */
include("config.inc.php");                                                  // SimpleSite Configuration File

/**
 * SimpleDB class for database work.
 *
 * @include includes/classes/simpledb.class.php
 */
include("includes/classes/SimpleDB.php");                             // SimpleDB DBO class

/**
 * SimpleUtils class which is basically the parent/grandparent of 
 * all other core classes.
 *
 * @include includes/classes/simpleutils.class.php
 */
include("includes/classes/SimpleUtils.php");                          // Generic utility functions

/**
 * simpleDisplayI interface which should be implemented by any class
 * attempting to change the template parsing process.
 *
 * @include includes/interfaces/simpledisplay.interface.php
 */
include("includes/interfaces/simpleDisplayI.php");                 // Interface for SimpleDisplay to allow a compatible replacement

/**
 * SimpleDisplay class which is the default parser for SimpleSite.
 *
 * @include includes/classes/simpledisplay.class.php
 */
include("includes/classes/SimpleDisplay.php");                        // Display functions

/**
 * simpleModuleI interface which should be implemented by all modules.
 *
 * @include includes/interfaces/simplemodule.interface.php
 */
include("includes/interfaces/simpleModuleI.php");                  // Interface for modules (simpleModuleI)

/**
 * SimpleModule abstract class which should be extended by all modules.
 *
 * @include includes/abstracts/simplemodule.abstract.php
 */
include("includes/abstracts/SimpleModule.php");                    // Abstract class for modules (default properties)

/**
 * SimpleSite abstract class which should be extended by all controllers.
 *
 * @include includes/sites/simplesite.abstract.php
 */
include("includes/abstracts/SimpleSite.php");                      // Main Site Abstract Class - Magic Happens Here 

/**
 * Controller class, set dynamically in the configurations.
 *
 * @include includes/sites/default_controller.class.php
 */
include("includes/sites/${configs['default_controller']}.php");       // Class Implementing SimpleSite
?>
