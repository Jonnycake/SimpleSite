<?php
/*
 *    SimpleSite Utils Class v2.1: Basic back-end utilities.
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

/**
 * SimpleUtils class all globally needed methods should be placed here.
 *
 * @package SimpleSite Core
 * @author Jonathan Stockton <jonathan@simplesite.ddns.net>
 */

/**
 * This file can not be accessed directly.
 */
if(SIMPLESITE!=1)
	die("Can't access this file directly.");

/**
 * SimpleUtils class - basically the parent/grandparent of all core classes.
 *
 * @package SimpleSite Core
 */
class SimpleUtils
{
	/**
	 * Array of paths to classes/interfaces
	 *   Key should be the class/interface name and value filename
	 *
	 * @var array
	 */
	protected static $include_file_list=array();

	/**
	 * Array of enabled modules
	 *
	 * @var array
	 */
	protected $mods=array();

	/**
	 * Array of loaded modules
	 *
	 * @var array
	 */
	protected $loaded=array();

	/**
	 * Check if the current user is blocked
	 *
	 * @param array $configs The configuration array set up in config.inc.php
	 * @return bool
	 */
	public function checkBlocked($configs=array())
	{
		if(!isset($configs['blocked'])) $configs['blocked']=array();
		if(in_array($_SERVER['REMOTE_ADDR'],$configs['blocked']))
			return TRUE;
		return FALSE;
	}

	/**
	 * Create a directory tree
	 *
	 * @param string $curdir The directory to start in.
	 * @param int $maxdepth The maximum depth to recurse to (-1 means infinite)
	 * @param int $depth The current depth in the structure (incremented on recursion)
	 * @return array The directory tree
	 */
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

	/**
	 * Create a debugging instance
	 *
	 * @return void
	 */
	public function createDbgInstance()
	{
		$this->debug=SimpleDebug::createInstance(get_class($this));
	}

	/**
	 * Recursively delete an entire directory
	 *
	 * @param string $curdir The directory to delete
	 * @param int $depth The current depth
	 * @return void
	 *
	 * @todo SimpleDirectory class
	 */
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

	/**
	 * Recursively copy an entire directory
	 *
	 * @param string $curdir The original directory to copy
	 * @param string $newdir The new directory path to copy to
	 * @return void
	 *
	 * @todo SimpleDirectory class
	 */
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

	/**
	 * Create a feed based on templates and a data array
	 *
	 * @param string $feedTemplate The path to the template which defines the output of the feed.
	 * @param array $dataArr The array of associative arrays containing information to be used in the feed.
	 * @param array $configs Associative array of configurations set in config.inc.php
	 * @param bool $bbencode Whether or not to bbencode the information
	 * @return string A feed
	 */
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

	/**
	 * Run an external application and save the output
	 *
	 * @param string $path The path to the application you wish to run
	 * @return string The output of the application.
	 */
	public function runApp($path)
	{
		ob_start();
		include($path);
		$output=ob_get_contents();
		ob_end_clean();
		return $output;
	}

	/**
	 * Load list of autoloadables
	 *
	 * @param array $configs Associative array of configurations set by config.inc.php
	 * @return void
	 */
	public function loadComponents($configs=array())
	{
		$path = "";
		self::$include_file_list = array();
		$componentFiles = $this->createDirTree($configs["path"]["includes"]."components", 1);
		foreach($componentFiles as $file) {
			$componentName = explode(".json", $file);
			$componentName = $componentName[0];

			$componentFilePath = $configs["path"]["includes"]."components/${file}";
			$componentConfig = json_decode(file_get_contents($componentFilePath), true);

			// Consolidated to match loadComponentFiles() and checkComponentFiles()
			foreach($componentConfig["include_files"] as $type => $fileList) {
				foreach($fileList as $className => $file) {
					self::$include_file_list[$className] = $configs["path"]["includes"]."${type}/${componentName}/${file}";
				}
			}
		}
		$this->loadModules($configs);
	}

	/**
	 * Load the include files listed by the specified component.
	 *
	 * @param string $name The name of the component you wish to load.
	 * @param array $configs The configurations set by config.inc.php
	 * @param bool $allowFail Whether failures should be allowed (missing files).
	 *
	 * @return bool Whether or not the component was successfully loaded.
	 */
	public static function loadComponentFiles($name, $configs, $allowFail = false)
	{
		SimpleDebug::logInfo("Attempting to load the component '${name}'...");
		$path = $configs['path']['includes'] . "components/${name}.json";
		if(file_exists($path)) {
			$componentJSON = file_get_contents($path);
			$component = json_decode($componentJSON, true);
			foreach($component['include_files'] as $type => $fileList) {
				foreach($fileList as $file) {
					$filePath = $configs['path']['includes'] . "${type}/${name}/${file}";
					if(file_exists($filePath)) {
						include($filePath);
					} else {
						if(!$allowFail) {
							SimpleDebug::logException(new Exception("The component '${name}' appears to be corrupted - '${filePath}' does not exist."));
							return false;
						}
					}
				}
			}
		} else {
			SimpleDebug::logException(new Exception("The component '${name}' does not exist!"));
			return false;
		}
		return true;
	}

	/**
	 * Check the specified component's dependencies
	 *
	 * @param string $name The name of the component you wish to check.
	 * @param array $configs The configurations set by config.inc.php
	 *
	 * @return bool Whether or not the dependencies are in place.
	 */
	public static function checkComponentFiles($name, $configs)
	{
		SimpleDebug::logInfo("Checking dependencies for the component '${name}'");
		$path = $configs['path']['includes'] . "components/${name}.json";
		if(file_exists($path)) {
			$componentJSON = file_get_contents($path);
			$component = json_decode($componentJSON, true);

			// Check PHP includes
			foreach(@$component['include_files'] as $type => $fileList) {
				foreach($fileList as $file) {
					$filePath = $configs['path']['includes'] . "${type}/${name}/${file}";
					if(!file_exists($filePath)) {
						return false;
					}
				}
			}

			// Check HTML assets
			foreach(@$component['assets'] as $file) {
				if(!file_exists($_SERVER['DOCUMENT_ROOT'] . $configs['path']['assets'] . "${file}")) {
					return false;
				}
			}

			// Check template files
			foreach(@$component['templates'] as $file) {
				if(!file_exists($_SERVER['DOCUMENT_ROOT'] . $configs['path']['root'] . $configs['path']['template_subdir'] . "${file}")) {
					return false;
				}
			}
		} else {
			SimpleDebug::logException(new Exception("The component '${name}' does not exist!"));
			return false;
		}
		return true;
	}

	/**
	 * Load the list of modules that are enabled/disabled
	 *
	 * @param array $configs Associative array of configurations set by config.inc.php
	 * @return void
	 */
	public function loadModules($configs=array())
	{
		SimpleDebug::logInfo("loadModules($enabled)");
		$this->mods=array();
		$modsdir=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/mods/";
		$this->mods = json_decode(file_get_contents($modsdir."mods.json"), true);
	}

	/**
	 * Check if the files exist.
	 *
	 * @param array $reqFiles An array of files to check
	 * @param $configs The associative array of configurations set by config.inc.php
	 * @return bool Whether or not all of the files exist.
	 */
	public function checkReqFiles($reqFiles,$configs=array())
	{
		foreach($reqFiles as $file)
			if(!(is_file($file))) // Maybe we can use some default path stuff here
				return FALSE; // Also should probably add some debug output here
		return TRUE;
	}

	/**
	 * Install required files
	 *
	 * @param array $defaultFiles An associative array of files to install with their base64 equivilents as the values.
	 * @param $configs The associative array of configurations set by config.inc.php
	 * @return bool Whether or not the files can be installed.
	 */
	public function installReqFiles($defaultFiles,$configs=array())
	{
		// Maybe we should make a SimpleInstaller class....hmmmm
		SimpleDebug::logInfo("Installing required files...");
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
					SimpleDebug::logInfo("Error on installing file: ${name}.");
					SimpleDebug::logException($e);
					return false;
				}
			}
		}
		SimpleDebug::logInfo("Installed.");
		return true;
	}

	/**
	 * Check if the database tables exist.
	 *
	 * @param array $reqTbls An array of tables to check
	 * @param $configs The associative array of configurations set by config.inc.php
	 * @return bool Whether or not all of the tables exist.
	 */
	public function checkReqTbls($reqTbls,$configs=array())
	{
		$dbconf=$configs['database'];
		foreach($reqTbls as $table)
		{
			// Yeah, we definitely need to finish up SimpleDB
			if(!count($this->db->sdbGetColumns($dbconf['tbl_prefix'].$table))) // We should make a better way to do this in SimpleDB
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Install the required database tables.
	 *
	 * @param array $defaultTbls An 3d associative array of tables to install; example: array("table" => array("column"=>"properties"))
	 * @param $configs The associative array of configurations set by config.inc.php
	 * @return void
	 */
	public function installReqTbls($defaultTbls,$configs=array())
	{
		// We need to add a table creator to SimpleDB....
		SimpleDebug::logInfo("Installing required tables...");
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
			SimpleDebug::logInfo("Query: $query");
			$this->db->rawQry($query);
		}
		SimpleDebug::logInfo("Installed.");
	}

	/**
	 * Parse a conditional statement
	 *
	 * @param array $match The match by preg_match
	 * @return string What to replace the conditional with.
	 *
	 * @todo Conditional helper class
	 */
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

	/**
	 * Filter user input
	 *
	 * @param string $input The input that should be sanitized
	 * @param bool $db Whether or not to filter for the database
	 * @return string The sanitized version of $input
	 *
	 * @todo SimpleInput
	 */
	public function simpleFilter($input,$db=true)
	{
		SimpleDebug::logInfo("simplefilter");
		return str_replace("{","&#123;",str_replace("}","&#125;",htmlspecialchars((($db)?$this->db->quote($input):$input))));
	}

	public static function enabledFilter($modConfig)
	{
		return $modConfig["enabled"];
	}
}
?>
