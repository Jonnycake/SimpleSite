<?php
/*
 *    SimpleSite Main Class v0.1: Main program logic.
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
class SimpleSite extends SimpleDisplay
{
	public function __call($method, $args)
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: __call($method,\$args)\n";
		if (@(isset($this->$method) && is_callable($this->$method)))
		{
			$func = $this->$method;
			return $func($args);
		}
		else
			die("Invalid method: $method");
	}

	function __construct()
	{
		include("config.inc.php");
		if(@($_GET['debug'])==1)
			echo "Dbg: __construct()\n";
		$mods=$this->loadModules();
		if(@(in_array($_GET['mod'],$mods)))
			$this->showSite($_GET['mod']);
		else 
		{
			if($configs['default_mod']!="")
				$this->showSite($configs['default_mod']);
			else
				$this->showSite("");
		}
	}
}
?>
