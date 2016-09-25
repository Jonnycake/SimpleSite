<?php
/*
 *    SimpleSite SimpleModule Interface: Basic structure of modules.
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

/**
 * Defines the interface for modules to implement
 *
 * @package SimpleSite Core
 * @author Jonathan Stockton <jonathan@simplesite.ddns.net>
 */

/**
 * Can't be accessed directly
 */
if(SIMPLESITE!=1)
	die("Can't access this file directly.");

/**
 * Interface for modules to implement
 */
interface simpleModuleI
{
	/**
	 * Should replace constants
	 *
	 * @param string $content The current content of the output (prior to parsing)
	 * @return string The content after the parsing operations the module needs
	 */
	public function sideparse($content);

	/**
	 * Should return the name of the page to use to display
	 *
	 * @return mixed The page that is chosen: string (template to be used from theme - empty is overall), array (ex: array("mod"=>"name")), or null (blank)
	 */
	public function choosePage();

	/**
	 * Checks if the module is installed
	 *
	 * @return bool Whether or not the module is installed.
	 */
	public function isInstalled();

	/**
	 * Installs the module
	 *
	 * @return bool Whether or not the module could be installed
	 */
	public function install();

	/**
	 * Retrieve the content
	 *
	 * @return string What to replace the {CONTENT} constant with.
	 */
	public function getContent();

	/**
	 * Retrieve data based on arguments passed through API
	 *
	 * @return array The data that should be sent back to the client
	 */
	public static function api($route, $configs = array());
}
?>
