<?php
/*
 *    SimpleSite API Module v0.1: API for a data-driven SimpleSite-based website.
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
if(SIMPLESITE!=1)
	die("Can't access this file directly.");
class api extends SimpleModule
{
	public static $info=array(  "author"  => "Jon Stockton",
				    "name"=> "SimpleSite API",
				    "version" => "0.1",
				    "date"=> "October 6, 2015"
	);
	public $route = "";

	public function choosePage()
	{
		global $route;
		$route = explode("/", @$_GET['r']);

		if(!(count($route) >= 1)) {
			header("HTTP/1.1 500 Internal Server Error");
			return "";
		}

		$format = 0;
		switch($route[0]){
			case "json":
				$format = SimpleDisplay::FORMAT_JSON;
				break;
			case "xml":
				$format = SimpleDisplay::FORMAT_XML;
				break;
			case "array":
				$format = SimpleDisplay::FORMAT_ARRAY;
				break;
			case "serialize":
				$format = SimpleDisplay::FORMAT_SERIALIZED;
				break;
			case "base64":
				$format = SimpleDisplay::FORMAT_BASE64;
				break;
			default:
				header("HTTP/1.1 500 Internal Server Error");
				return "";
		}

		return $format;
	}
	public function sideparse($content,$configs=array())
	{
		return $content;
	}
	public function isInstalled($configs=array())
	{
		return true;
	}
	public function install($configs=array())
	{
		return TRUE;
	}
	public function uninstall($configs=array())
	{
		return FALSE;
	}
	public function getContent($configs=array())
	{
		return "";
	}
}
?>
