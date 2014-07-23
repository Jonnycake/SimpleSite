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
 
if(SIMPLESITE!=1)
	die("Can't access this file directly.");
interface simpleModuleI
{
	public function sideparse($content);
	public function choosePage();
	public function isInstalled();
	public function install();
	public function getContent();
}
?>