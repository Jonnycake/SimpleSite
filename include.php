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
define('SIMPLESITE',1);

include("config.inc.php");                                                  // SimpleSite Configuration File
include("includes/classes/simpledb.class.php");                             // SimpleDB DBO class
include("includes/classes/simpleutils.class.php");                          // Generic utility functions
include("includes/interfaces/simpledisplay.interface.php");                 // Interface for SimpleDisplay to allow a compatible replacement
include("includes/classes/simpledisplay.class.php");                        // Display functions
include("includes/interfaces/simplemodule.interface.php");                  // Interface for modules (simpleModuleI)
include("includes/abstracts/simplemodule.abstract.php");                    // Abstract class for modules (default properties)
include("includes/abstracts/simplesite.abstract.php");                      // Main Site Abstract Class - Magic Happens Here 
include("includes/sites/${configs['default_controller']}.class.php");       // Class Implementing SimpleSite
?>
