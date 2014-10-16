<?php
/*
 *    DefaultSite Class 1.0: Handles display of the website.
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
class DefaultSite extends SimpleSite
{
	public function __construct($configs=array())
	{
		SimpleDebug::logInfo("SimpleSite->__construct()");
		spl_autoload_register('SimpleSite::simpleLoader');

		// Configs
		if(!(isset($this->configs)))
		{
			if(count($configs)==0)
			{
				include("config.inc.php");
			}
			$this->configs=$configs;
			try
			{
				$this->db=new SimpleDB($this->configs['database'], $_GET['debug']);
				if($this->db->connected())
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
			catch(Exception $e)
			{
				SimpleDebug::logException($e);
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
}
?>
