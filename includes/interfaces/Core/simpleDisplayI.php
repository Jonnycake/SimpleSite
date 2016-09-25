<?php
/*
 *SimpleSite Display Interface: Basic structure of a SimpleDisplay class.
 *Copyright (C) 2014 Jon Stockton
 *
 *This program is free software: you can redistribute it and/or modify
 *it under the terms of the GNU General Public License as published by
 *the Free Software Foundation, either version 3 of the License, or
 *(at your option) any later version.
 *
 *This program is distributed in the hope that it will be useful,
 *but WITHOUT ANY WARRANTY; without even the implied warranty of
 *MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *GNU General Public License for more details.
 *
 *You should have received a copy of the GNU General Public License
 *along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Defines the interface for controllers to implement
 *
 * @package SimpleSite Core
 * @author Jonathan Stockton <jonathan@simplesite.ddns.net>
 */
if(SIMPLESITE!=1)
	die("Can't access this file directly.");

/**
 * Interface for controllers to implement
 */
interface simpleDisplayI
{
	const FORMAT_JSON = 1;
	const FORMAT_BASE64 = 2;
	const FORMAT_SERIALIZED = 3;
	const FORMAT_XML = 4;
	const FORMAT_ARRAY = 5;

	/**
	 * Read a template
	 *
	 * @param string $template The full path to the template
	 * @param string $mod The module to use to do side-parsing
	 * @return string The parsed content of the $template file
	 */
	public function readTemplate($template, $mod);

	/**
	 * Parse the template content
	 *
	 * @param string $content The content of the template
	 * @param string $mod The module to use for side-parsing
	 * @return string The parsed template content
	 */
	public function parseTemplate($content, $mod);

	/**
	 * Show the website
	 *
	 * @param string $mod The module to use for side-parsing
	 * @return void
	 */
	public function showSite($mod);

	/**
	 * Check if the display is installed properly
	 *
	 * @return bool Whether or not it is installed properly.
	 */
	public function displayIsInstalled();

	/**
	 * Install the display
	 *
	 * @return bool Whether or not the display could be installed
	 */
	public function displayInstall();

	/**
	 * Un-Install the display
	 *
	 * @return bool Whether or not the display could be uninstalled
	 */
	public function displayUninstall();
}
?>
