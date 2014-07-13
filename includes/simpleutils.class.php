<?php
/*
 *    SimpleSite Utils Class v1.1: Basic back-end utilities.
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
class SimpleUtils
{
	// Administrative Utils
	function checkBlocked($configs=array())
	{
		if(in_array($_SERVER['REMOTE_ADDR'],$configs['blocked']))
			return TRUE;
		return FALSE;
	}
	
	// Modules
	function loadModules($enabled=1)
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: loadModules($enabled)\n";
		include("config.inc.php");
		$mods=array();
		$modsdir=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/mods/".(($enabled==1)?"enabled":"disabled")."/";
		$dir=opendir($modsdir);
		while(@($file=readdir($dir)))
			if(preg_match("/(.*)\.mod\.php/si",$file,$matches) && (@$matches))
			{
				if(!class_exists($matches[1]))
					include("$modsdir/$file");
				if(class_exists($matches[1]))
					$mods[]=$matches[1];
				else
					if(@($_GET['debug'])==1)
						echo "Dbg: Invalid module.  ${matches[1]} class does not exist.\n";
			}
		return $mods;
	}
	function checkReqFiles($reqFiles,$configs=array())
	{
		foreach($reqFiles as $file)
		{
			if(!(is_file($file)))
				return FALSE;
		}
		return TRUE;
	}
	function installReqFiles($defaultFiles,$configs=array())
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: Installing required files...";
		foreach($defaultFiles as $name => $value)
		{
			$f=fopen($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/$name","w");
			fwrite($f,base64_decode($value));
			fclose($f);
		}
		if(@($_GET['debug'])==1)
			echo "installed\n";
	}
	function checkReqTbls($reqTbls,$configs=array())
	{
		$dbconf=$configs['database'];
		$conn=@mysql_connect($dbconf['host'],$dbconf['username'],$dbconf['password']);
		mysql_select_db($dbconf['database'],$conn);
		foreach($reqTbls as $table)
		{
			$res=@mysql_query("SELECT * FROM ${dbconf['tbl_prefix']}$table;");
			$err=mysql_errno();
			if($err==1146)
			{
				mysql_close($conn);
				return FALSE;
			}
		}
		mysql_close($conn);
		return TRUE;
	}
	function installReqTbls($defaultTbls,$configs=array())
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: Installing required tables...";
		$dbconf=$configs["database"];
		$conn=@mysql_connect($dbconf["host"],$dbconf["username"],$dbconf["password"]);
		@mysql_select_db($dbconf["database"]);
		foreach($defaultTbls as $name => $columns)
		{
			$x=0;
			$query="CREATE TABLE IF NOT EXISTS `${dbconf['tbl_prefix']}$name` (";
			foreach($columns as $column => $properties)
			{
				$x++;
				$query.="`$column` $properties".(($x<count($columns))?",":"");
			}
			$query.=");";
			mysql_query($query);
		}
		mysql_close($conn);
		if(@($_GET['debug'])==1)
			echo "installed.\n";
	}
	
	// User input utils
	function simpleFilter($input,$db=1)
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: simplefilter\n";
		return str_replace("{","&#123;",str_replace("}","&#125;",htmlspecialchars((($db==1)?mysql_real_escape_string($input):$input))));
	}
 }
 ?>
