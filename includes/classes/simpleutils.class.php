<?php
/*
 *    SimpleSite Utils Class v2.0: Basic back-end utilities.
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
class SimpleUtils
{
	protected $mods=array();
	protected $loaded=array();

	// Administrative Utils
	public function checkBlocked($configs=array())
	{
		if(!isset($configs['blocked'])) $configs['blocked']=array();
		if(in_array($_SERVER['REMOTE_ADDR'],$configs['blocked']))
			return TRUE;
		return FALSE;
	}
	public function createDirTree($curdir="./",$maxdepth=-1,$depth=0)
	{
		$tree=array();
		$dir=opendir($curdir);
		while(@($file=readdir($dir)) and ($depth!=$maxdepth))
			if(is_dir($curdir."/".$file) and !($file=="." or $file=="..") and !(is_link("$curdir/$file"))) // Maybe we can check more in depth with links
				$tree[$file]=$this->createDirTree("$curdir/$file",$maxdepth,$depth+1);
			else
				if($file!="." && $file!="..")
					$tree[$file]=$file;
		ksort($tree,SORT_STRING);
		return $tree;
	}

	public function createDbgInstance()
	{
		$this->instance=SimpleDebug::createInstance(get_class($this));
	}

	/* START: Replaced by SimpleDirectory */
	public function recursiveDirDelete($curdir="/tmp/",$depth=0)
	{
		// This can probably be re-designed to be more efficient - createDirTree, go from top down
		$dir=@opendir($curdir);
		while(@($file=readdir($dir)))
		{
			// Note: This function will fail if there is a link in the directory as the directory won't be empty and therefore can't be deleted
			if(is_dir($curdir."/".$file) and !($file=="." or $file=="..") and !(is_link("$curdir/$file"))) // Linked parent directories, .., and . create endless loops
			{
				if(!(@rmdir("$curdir/$file")))
				{
					$this->recursiveDirDelete("$curdir/$file",$depth+1);
					@rmdir("$curdir/$file");
				}
			}
			else
				@unlink("$curdir/$file");
		}
		@rmdir($curdir);
	}
	public function recursiveDirCopy($curdir="/tmp/",$newdir="./")
	{
		if(!(is_dir($newdir)))
		{
			@unlink($newdir); // Do we really want to do this?  This seems kind of weird to me
			@mkdir($newdir);
		}
		$dir=@opendir($curdir);
		while(($file=@readdir($dir)))
		{
			if(is_dir($curdir."/".$file) and !($file=="." or $file=="..") and !(is_link("$curdir/$file")))
			{
				@mkdir("$newdir/$file");
				$this->recursiveDirCopy("$curdir/$file",$newdir."/$file");
			}
			else if(!($file=="." or $file==".."))
			{
				@copy("$curdir/$file",$newdir."/$file");
			}
		}
	}
	/* END: Replaced By SimpleDirectory */

	// Output Utils
	public function arr2Feed($feedTemplate,$dataArr=array(),$configs=array(),$bbencode=false)
	{
		// This will get cleaned up by SimpleFile
		$content="";
		$template=$this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$feedTemplate,$_GET['mod']);
		$piece=$template;
		foreach($dataArr as $data)
		{
			while((preg_match("/{DataArr_([a-zA-Z0-9\-_]*)}/si",$piece,$match)) && $match)
			{
				if(array_key_exists($match[1],$data)){
					if($bbencode)
						$data[$match[1]]=$this->bbencode($data[$match[1]]); // Protected function in SimpleDisplay
					$piece=str_replace($match[0],$data[$match[1]],$piece);
				}
				else
					$piece=str_replace($match[0],"",$piece);
			}
			$content.=$piece;
			$piece=$template;
		}
		return $content;
	}
	public function runApp($path)
	{
		ob_start();
		include($path);
		$output=ob_get_contents();
		ob_end_clean();
		return $output;
	}

	// Modules
	public function loadModules($configs=array(),$enabled=true)
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: loadModules($enabled)".time()."\n";
		$this->mods=array();
		$modsdir=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/mods/".(($enabled)?"enabled":"disabled")."/";
		$dir=opendir($modsdir);
		while(@($file=readdir($dir)))
			if(preg_match("/(.*)\.mod\.php/si",$file,$matches) && (@$matches))
					$this->mods[]=$matches[1];
	}
	public function checkReqFiles($reqFiles,$configs=array())
	{
		foreach($reqFiles as $file)
			if(!(is_file($file))) // Maybe we can use some default path stuff here
				return FALSE; // Also should probably add some debug output here
		return TRUE;
	}
	public function installReqFiles($defaultFiles,$configs=array())
	{
		// Maybe we should make a SimpleInstaller class....hmmmm
		if(@($_GET['debug'])==1)
			echo "Dbg: Installing required files...".time();
		foreach($defaultFiles as $name => $value)
		{
			if(!(file_exists($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/$name")))
			{
				try
				{
					$f=fopen($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/$name","w");
					fwrite($f,base64_decode($value));
					fclose($f);
				}
				catch(Exception $e)
				{
					if(@($_GET['debug'])==1)
						echo "\nError on ${name}.".time();
					return false;
				}
			}
		}
		if(@($_GET['debug'])==1)
			echo "installed.".time()."\n";
		return true;
	}
	public function checkReqTbls($reqTbls,$configs=array())
	{
		$dbconf=$configs['database'];
		foreach($reqTbls as $table)
		{
			if(!count($this->db->sdbGetColumns($dbconf['tbl_prefix'].$table))) // We should make a better way to do this in SimpleDB
			{
				return false;
			}
		}
		return true;
	}
	public function installReqTbls($defaultTbls,$configs=array())
	{
		// We need to add a table creator to SimpleDB....
		if(@($_GET['debug'])==1)
			echo "Dbg: Installing required tables...".time();
		$dbconf=$configs["database"];
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
			$this->db->rawQry($query);
		}
		if(@($_GET['debug'])==1)
			echo "installed.".time()."\n";
	}

	// Template Conditional Handler
	public function tempConditional($match=array())
	{
		// This is really ugly, should probably be split up or have some sort of implementation in SimpleFile
		// I guess we could have another class - SimpleConditional...
		if(count($match)==2)
			return $match[1];
		switch($match[2])
		{
			case "eq":
				if($match[1]==$match[3])
				{
					if((preg_match("/(.*?)?({ELSE}|{ELIF ).*/si",$match[4],$matches2)) && $matches2)
						return $matches2[1];
					return $match[4];
				}
				else if((preg_match("/{ELIF \"(.*?)\" (eq|ne|gt|lt|gte|lte) \"(.*?)\"}(.*)/si",$match[4],$matches2))&& $matches2)
					return $this->tempConditional($matches2);
				else if((preg_match("/{ELSE}(.*)/si",$match[4],$matches2))&& $matches2)
					return $this->tempConditional($matches2);
				break;
			case "ne":
				if($match[1]!=$match[3])
				{
					if((preg_match("/(.*?)?({ELSE}|{ELIF ).*/si",$match[4],$matches2)) && $matches2)
						return $matches2[1];
					return $match[4];
				}
				else if((preg_match("/{ELIF \"(.*?)\" (eq|ne|gt|lt|gte|lte) \"(.*?)\"}(.*)/si",$match[4],$matches2))&& $matches2)
					return $this->tempConditional($matches2);
				else if((preg_match("/{ELSE}(.*)/si",$match[4],$matches2))&& $matches2)
					return $this->tempConditional($matches2);
				break;
			case "gt":
				if($match[1]>$match[3])
				{
					if((preg_match("/(.*?)?({ELSE}|{ELIF ).*/si",$match[4],$matches2)) && $matches2)
						return $matches2[1];
					return $match[4];
				}
				else if((preg_match("/{ELIF \"(.*?)\" (eq|ne|gt|lt|gte|lte) \"(.*?)\"}(.*)/si",$match[4],$matches2))&& $matches2)
					return $this->tempConditional($matches2);
				else if((preg_match("/{ELSE}(.*)/si",$match[4],$matches2))&& $matches2)
					return $this->tempConditional($matches2);
				break;
			case "lt":
				if($match[1]<$match[3])
				{
					if((preg_match("/(.*?)?({ELSE}|{ELIF ).*/si",$match[4],$matches2)) && $matches2)
						return $matches2[1];
					return $match[4];
				}
				else if((preg_match("/{ELIF \"(.*?)\" (eq|ne|gt|lt|gte|lte) \"(.*?)\"}(.*)/si",$match[4],$matches2))&& $matches2)
					return $this->tempConditional($matches2);
				else if((preg_match("/{ELSE}(.*)/si",$match[4],$matches2))&& $matches2)
					return $this->tempConditional($matches2);
				break;
			case "gte":
				if($match[1]>=$match[3])
				{
					if((preg_match("/(.*?)?({ELSE}|{ELIF ).*/si",$match[4],$matches2)) && $matches2)
						return $matches2[1];
					return $match[4];
				}
				else if((preg_match("/{ELIF \"(.*?)\" (eq|ne|gt|lt|gte|lte) \"(.*?)\"}(.*)/si",$match[4],$matches2))&& $matches2)
					return $this->tempConditional($matches2);
				else if((preg_match("/{ELSE}(.*)/si",$match[4],$matches2))&& $matches2)
					return $this->tempConditional($matches2);
				break;
			case "lte":
				if($match[1]<=$match[3])
				{
					if((preg_match("/(.*?)?({ELSE}|{ELIF ).*/si",$match[4],$matches2)) && $matches2)
						return $matches2[1];
					return $match[4];
				}
				else if((preg_match("/{ELIF \"(.*?)\" (eq|ne|gt|lt|gte|lte) \"(.*?)\"}(.*)/si",$match[4],$matches2))&& $matches2)
					return $this->tempConditional($matches2);
				else if((preg_match("/{ELSE}(.*)/si",$match[4],$matches2))&& $matches2)
					return $this->tempConditional($matches2);
				break;
		}
		return "";
	}

	// User input utils
	public function simpleFilter($input,$db=true)
	{
		if(@($_GET['debug'])==1)
			echo "Dbg: simplefilter".time()."\n";
		return str_replace("{","&#123;",str_replace("}","&#125;",htmlspecialchars((($db)?$this->db->quote($input):$input))));
	}
 }
 ?>
