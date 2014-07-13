<?php
/*
 *    SimpleSite Main Class v1.5: Main program logic.
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
class SimpleSite extends SimpleDisplay
{
	public function autoload($name)
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: Attempting to autoload $name...";
		if((in_array($name,$this->mods)) && !(in_array($name,$this->loaded)))
		{
			@include($_SERVER['DOCUMENT_ROOT'].$this->configs['path']['root']."includes/mods/enabled/${name}.mod.php");
			if(!(class_exists($name)))
			{
				if(@($_GET['debug']==1)) echo "Error!";
			}
			else
			{
				$this->loaded[]=$name;
				if(@($_GET['debug'])==1) echo "Good.\n";
			}
		}
	}
	public function __call($method, $args)
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: SimpleSite->__call($method,\$args)\n";
		if(@(isset($this->$method) && is_callable($this->$method)))
		{
			$func = $this->$method;
			return $func($args);
		}
		else
			die("Invalid method: $method");
	}

	function __construct()
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: SimpleSite->__construct()\n";
		spl_autoload_register('SimpleSite::autoload');

		// Configs
		if(!(isset($this->configs)))
		{
			include("config.inc.php");
			$this->configs=$configs;
			$this->db=new SimpleDB($this->configs['database']);

			if(!$this->db->sdbGetErrorLevel())
			{
				// Constants
				$constantsTbl=$this->db->openTable('constants');
				$constantsTbl->select(array('name','value'));
				$constants=$constantsTbl->sdbGetRows();
				if($constants!=false)
					foreach($constants as $row)
					{
						define($row->getName(),$row->getValue());
					}
				// Block List
				$blockedTbl=$this->db->openTable('blocked');
				$blockedTbl->select(array('remote_addr'));
				$blocked=$blockedTbl->sdbGetRows();
				if($blocked!=false)
					foreach($blocked as $row)
						$this->configs['blocked'][]=$row->getRemote_addr();
			}
		}
		if($this->checkBlocked())
			die("Your IP has been blocked, please contact the administrator for more information.");

		$this->loadModules($this->configs);
		if(@(in_array($_GET['mod'],$this->mods)))
			$this->showSite($_GET['mod']);
		else 
		{
			$this->showSite($configs['default_mod']);
		}
	}

	function __destruct()
	{
		$this->db->__destruct();
	}
}
?>
