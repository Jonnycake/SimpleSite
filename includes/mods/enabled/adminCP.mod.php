<?php
/*
 *    SimpleSite AdminCP Module v1.0: Admin page for a SimpleSite-based website.
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
class adminCP extends SimpleDisplay implements simpleModule
{
	public static $info=array( "author"  => "Jon Stockton",
						"name"    => "SimpleAdmin",
						"version" => "1.0",
						"date"    => "November 22, 2012"
					 );
	public function sideparse($content,$configs=array())
	{
		if($_SESSION['is_admin'])
			switch($_GET['act'])
			{
				case "dbAdmin":
					$content=$this->dbAdmin($content,$configs);
					$content=str_replace("{QUERYSUCCESS}","",$content);
					break;
				case "modAdmin":
					$content=$this->modAdmin($content,$configs);
					break;
				case "configAdmin":
					$content=$this->configAdmin($content,$configs);
					break;
				case "templateAdmin":
					$content=$this->templateAdmin($content,$configs);
					break;
				case "themeMgr":
					$content=$this->themeMgr($content,$configs);
					break;
				case "testEnv":
					$content=$this->testEnvironment($content,$configs);
					break;
				case "widgetAdmin":
					$content=$this->widgetAdmin($content,$configs);
					break;
			}
		else
		{
			if($_GET['act']=="login")
				$content=str_replace("{LOGINSUCCESS}","Login failed, please try again.",$content);
			else
				$content=str_replace("{LOGINSUCCESS}","",$content);
		}
			
		if((preg_match("/{ACTIONPAGE}/si",$content,$match)))
			$content=str_replace("{ACTIONPAGE}",$this->getActionPage($configs),$content);
		return $content;
	}
	public function choosePage($configs=array())
	{
		if($_GET['act']=="login")
		{
			$dbconf=$configs['database'];
			$conn=@mysql_connect($dbconf['host'],$dbconf['username'],$dbconf['password']);
			mysql_select_db($dbconf['database'],$conn);
			$res=@mysql_query("SELECT * FROM ${dbconf['tbl_prefix']}admins WHERE username='".mysql_real_escape_string($_POST['username'])."' AND passwd='".md5($_POST['password'])."';");
			if(mysql_num_rows($res))
				$_SESSION['is_admin']=1;
			mysql_close($conn);
		}
		else if($_GET['act']=="logout")
		{
			$_SESSION['is_admin']=0;
		}
		return "";
	}
	public function isInstalled($configs=array())
	{
		return TRUE;
	}
	public function install($configs=array())
	{
		return TRUE;
	}
	public function getContent($configs=array(),$actionpage="")
	{
		if($_SESSION['is_admin']==1)
			return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP.template","adminCP");
		else
			return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_login.template","adminCP");
	}
	public function getActionPage($configs)
	{
		switch($_GET['act'])
		{
			case "configAdmin":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_configAdmin.template","adminCP");
			case "dbAdmin":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_dbAdmin.template","adminCP");
			case "modAdmin":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_modAdmin.template","adminCP");
			case "templateAdmin":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_templateAdmin.template","adminCP");
			case "themeMgr":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_themeMgr.template","adminCP");
			case "testEnv":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_testEnv.template","adminCP");
			case "widgetAdmin":
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_widgetAdmin.template","adminCP");
			default:
				return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_welcome.template","adminCP");
		}
		return "";
	}
	public function uninstall()
	{
		return TRUE;
	}
	
	function recursiveDirDelete($curdir="/tmp/",$depth=0)
	{
		$dir=@opendir($curdir);
		while(@($file=readdir($dir)))
		{
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
	function recursiveDirCopy($curdir="/tmp/",$newdir="./")
	{
		if(!(is_dir($newdir)))
		{
			@unlink($newdir);
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
	function createDirTree($curdir="./",$maxdepth=-1,$depth=0)
	{
		$tree=array();
		$dir=opendir($curdir);
		while(@($file=readdir($dir)) and ($depth!=$maxdepth))
			if(is_dir($curdir."/".$file) and !($file=="." or $file=="..") and !(is_link("$curdir/$file")))
				$tree[$file]=$this->createDirTree("$curdir/$file",$maxdepth,$depth+1);
			else
				if($file!="." && $file!="..")
					$tree[$file]=$file;
		ksort($tree,SORT_STRING);
		return $tree;
	}
	function genDirTreeOut($tree,$curdir="./",$depthdelim="\t",$template="{DEPTH}* {FILE}\n",$dirdisplay="[DIR] ",$type=0,$prevdirs="",$depth=0)
	{
		$output="";
		if(is_array($tree))
			foreach($tree as $k=>$v)
				if(is_dir("$curdir/$k") and !(is_link("$curdir/$k")))
				{
					include("includes/config.inc.php");
					$output.=str_replace("{TYPE}","${type}_",str_replace("{PREVDIRS}",$prevdirs,str_replace("{SELECTED}",(@($configs['filename']=="${type}_${prevdirs}${k}")?" selected=\"selected\"":(($type==2 && $k==$configs['default_theme'])?" selected=\"selected\"":"")),str_replace("{DEPTH}",str_repeat($depthdelim,$depth),str_replace("{FILE}",$dirdisplay.$this->simpleFilter($k,0),$template)).$this->genDirTreeOut($tree[$k],"$curdir/$k",$depthdelim,$template,$dirdisplay,$type,$prevdirs."${k}/",$depth+1))));
				}
				else
					if(!(is_link("$curdir/$k")))
						$output.=str_replace("{TYPE}","${type}_",str_replace("{PREVDIRS}",$prevdirs,str_replace("{SELECTED}",(@($_POST['filename']=="${type}_${prevdirs}${k}")?" selected=\"selected\"":""),str_replace("{DEPTH}",str_repeat($depthdelim,$depth),str_replace("{FILE}",$this->simpleFilter($k,0),$template)))));
		return $output;
	}
	
	// Config File Administration
	public function configAdmin($content,$configs)
	{
		if(@($_GET['func'])=="save")
		{
			$f=@fopen($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/config.inc.php","w");
			@fwrite($f,$_POST['filecontent']);
			@fclose($f);
		}
		return str_replace("{FILECONTENT}",str_replace("{","&#123;",str_replace("}","&#125;",htmlspecialchars(file_get_contents($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/config.inc.php")))),$content);
	}
	
	// Database Administration
	public function dbAdmin($content,$configs)
	{
		$conn=@mysql_connect($configs['database']['host'],$configs['database']['username'],$configs['database']['password']);
		switch($_GET['func'])
		{
			case "dbSelect":
				$_POST['curdb']=$_POST['dbname'];
				break;
			case "tblInteract":
				switch($_POST['submit'])
				{
					case "INSERT":
						if($GLOBALS["funcsperformed"]==0)
						{
							$GLOBALS["funcsperformed"]++;
							$keys=array_keys($_POST);
							$inserts=preg_grep("/insert_(.*)/si",$keys);
							$query="INSERT INTO `".$_POST['curdb']."`.`".$_POST['tblName']."` (";
							$arrlen=count($inserts);
							$curkey=0;
							foreach($inserts as $key)
							{
								preg_match("/insert_(.*)/si",$key,$match);
								if($match[1]!="")
									$query.="`${match[1]}`".((++$curkey<$arrlen)?",":"");
							}
							$query.=") VALUES (";
							$curkey=0;
							foreach($inserts as $key)
							{
								preg_match("/insert_(.*)/si",$key,$match);
								if($match[1]!="")
									$query.="'".mysql_real_escape_string($_POST[$match[0]])."'".((++$curkey<$arrlen)?",":"");
							}
							$query.=");";
							@mysql_query($query);
						}
						break;
					case "DELETE":
						if($GLOBALS["funcsperformed"]==0)
						{
							$GLOBALS["funcsperformed"]++;
							$keys=array_keys($_POST);
							$deletes=preg_grep("/delete_(.*)/si",$keys);
							$query="DELETE FROM `".$_POST['curdb']."`.`".$_POST['tblName']."` WHERE ";
							$arrlen=count($deletes);
							$curkey=0;
							foreach($deletes as $key)
							{
								preg_match("/delete_(.*)_(.*)/si",$key,$match);
								if($match[1]!="" && $match[2]!="")
									$query.="`${match[1]}`='${match[2]}'".((++$curkey<$arrlen)?" OR ":"");
							}
							$query.=";";
							@mysql_query($query);
						}
						break;
					case "UPDATE":
						if($GLOBALS["funcsperformed"]==0)
						{
							$GLOBALS["funcsperformed"]++;
							$updates=array();
							
							// Check for changes
							$keys=array_keys($_POST);
							$updateKeys=preg_grep("/update_(.*)/si",$keys);
							foreach($updateKeys as $updateKey)
							{
								preg_match("/update_(.*)_(.*)_(.*)_(.*)/si",$updateKey,$match);
								if($match[1]!="" && $match[2]!="" && $match[3]!="")
								{
									$primaryKey=$match[1];
									if($match[4]!=$_POST[$match[0]])
										$updates[$match[2]][$match[3]]=$_POST[$match[0]];
								}
							}
							
							// Execute changes
							$keys=array_keys($updates);
							foreach($keys as $key)
							{
								$x=0;
								$cKeys=array_keys($updates[$key]);
								$max=count($cKeys);
								$query="UPDATE `".$_POST['curdb']."`.`".$_POST['tblName']."` SET ";
								foreach($updates[$key] as $ck=>$cv)
									$query.="`$ck`='$cv'".(($x++<$max-1)?",":"");
								$query.=" WHERE `$primaryKey`='$key';";
								@mysql_query($query);
							}
						}
						break;
					case "Query":
						if($GLOBALS["funcsperformed"]==0)
						{
							$GLOBALS["funcsperformed"]++;
							$querysuccess=@mysql_query($_POST['query']);
							$GLOBALS["mysqlsuccess"]=($querysuccess==FALSE)?mysql_error():"Query successful.";
						}
						else
							$content=str_replace("{QUERYSUCCESS}",$GLOBALS["mysqlsuccess"],$content);
						break;
				}
		}
		$content=str_replace("{CURDB}",$_POST['curdb'],$content);
		$databases="";
		$res=@mysql_query("SHOW DATABASES;");
		while(($row=mysql_fetch_array($res)))
			$databases.="<option value=\"${row[0]}\"".(($row[0]==$_POST['curdb'])?" selected=\"selected\"":"").">${row[0]}</option>";
		$content=str_replace("{DATABASES}",$databases,$content);
		$tables=array();
		$subcontent="";
		if($_POST['curdb']!="")
		{
			mysql_select_db($_POST['curdb']);
			
			// Get table names
			$res=@mysql_query("SHOW TABLES;");
			while(($row=mysql_fetch_array($res)))
			{
				// Create a new table based on information retrieved
				$subcontent.=file_get_contents($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/adminCP_dbAdmin_table.template");
				$numcols=0;
				$colnames="";
				$currow=0;
				$curcol=0;
				$numrows=0;
				$values="";
				$inputs="";
				$primaryKey=array();
				$columns=array();
				
				// Get column names
				$res2=@mysql_query("SHOW COLUMNS IN `${row[0]}`;");
				while(($row2=mysql_fetch_array($res2)) && ++$numcols)
				{
					$tables[$row[0]][$row2[0]]=array();
					$columns[]=$row2[0];
					$colnames.="<td>${row2[0]}</td>";
					if($row2[3]=="PRI")
						$primaryKey[]=$numcols-1;
				}
				
				// Get values
				$res2=@mysql_query("SELECT * FROM `${row[0]}`;");
				while(($row2=mysql_fetch_array($res2)) && ++$numrows)
					foreach($row2 as $k=>$v)
						$tables[$row[0]][$k][]=$v;
				
				while($currow<$numrows)
				{
					while($curcol<$numcols)
						$values.="<td>".((in_array($curcol,$primaryKey))?"<input type=\"checkbox\" name=\"delete_${columns[$curcol]}_".htmlspecialchars($tables[$row[0]][$curcol][$currow])."\" value=\"".htmlspecialchars($tables[$row[0]][$curcol][$currow])."\">":"")."<input type=\"text\" style=\"width:70%;float:right;\" name=\"update_".$columns[$primaryKey[0]]."_".$tables[$row[0]][$primaryKey[0]][$currow]."_".$columns[$curcol]."_".htmlspecialchars($tables[$row[0]][$curcol][$currow])."\" value=\"".htmlspecialchars($tables[$row[0]][$curcol++][$currow])."\"/></td>";
					$curcol=0;
					$values.=(($currow+1<$numrows)?"</tr><tr>":"");
					$currow++;
				}
				
				// Prepare INSERT
				$curcol=0;
				foreach($tables[$row[0]] as $k=>$v)
					if(++$curcol<=$numcols)
						$inputs.="<td><input style=\"width:70%;float:right;\" type=\"text\" name=\"insert_$k\"/></td>";
				// Do replacements
				$subcontent=str_replace("{NUMCOLS}",$numcols,$subcontent);
				$subcontent=str_replace("{TBLNAME}",$row[0],$subcontent);
				$subcontent=str_replace("{COLNAMES}",$colnames,$subcontent);
				$subcontent=str_replace("{VALUES}",$values,$subcontent);
				$subcontent=str_replace("{INPUTS}",$inputs,$subcontent);
				$subcontent=str_replace("{CURDB}",$_POST['curdb'],$subcontent);
			}
		}
		$content=str_replace("{TABLES}",$subcontent,$content);
		return $content;
	}
	
	// Module Administration
	public function modAdmin($content,$configs)
	{
		if(@($_GET['func'])=="toggleEnabled")
			$this->toggleMod(@($_GET['module']),@($_GET['currentState']),$configs);
		else if(@($_GET['func'])=="upload")
			$this->uploadMod($configs);
		$modulestext="";
		$modsAvailable=array("enabled"=>$this->loadModules(),"disabled"=>$this->loadModules(0));
		sort($modsAvailable["enabled"]);
		sort($modsAvailable["disabled"]);
		return str_replace("{MODULES}",$this->mods2Feed($modsAvailable,$configs),$content);
	}
	public function mods2Feed($modsAvailable,$configs)
	{
		$feed="";
		$x=0;
		foreach($modsAvailable as $mods)
		{
			$x++;
			foreach($mods as $mod)
			{
				$f=@fopen($_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/adminCP_modAdmin_modules.template","r");
				while(($line=fgets($f)))
					$feed.=$line;
				$feed=str_replace("{MODFILE}",$mod,$feed);
				$feed=str_replace("{NAME}",((isset($mod::$info))?((isset($mod::$info["name"]))?$mod::$info["name"]:$mod):$mod),$feed);
				$feed=str_replace("{AUTHOR}",((isset($mod::$info))?((isset($mod::$info["author"]))?$mod::$info["author"]:"No data..."):"No data..."),$feed);
				$feed=str_replace("{DATE}",((isset($mod::$info))?((isset($mod::$info["date"]))?$mod::$info["date"]:"No data..."):"No data..."),$feed);
				$feed=str_replace("{ENABLED}",(($x==1)?"Yes":"No"),$feed);
			}
		}
		return $feed;
	}
	public function toggleMod($module,$currentState,$configs)
	{
		$file=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/mods/".((strtolower($currentState)=="yes")?"enabled":"disabled")."/$module.mod.php";
		$newfile=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/mods/".((strtolower($currentState)=="yes")?"disabled":"enabled")."/$module.mod.php";
		if(is_file($file))
			if(copy($file,$newfile))
				unlink($file);
	}
	public function uploadMod($configs)
	{
		$error=0;
		$zipname=$_FILES['rfile']['name'];
		$tmpname=$_FILES['rfile']['tmp_name'];
		if(preg_match("/([^\/])*.zip/si",$zipname,$match) && $match[0]!="")
			$modname=str_replace(".zip","",$match[0]);
		else $error=1;
		$extractDir=$configs['path']['tmpdir']."/$modname";
		$zip=new ZipArchive();
		if($zip->open($tmpname) && $error==0)
		{
			$zip->extractTo($extractDir);
			$zip->close();
			if(is_dir("$extractDir/includes") && is_dir("$extractDir/templates"))
			{
				if(is_file("$extractDir/includes/$modname.mod.php"))
				{
					if(copy("$extractDir/includes/$modname.mod.php",$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/mods/disabled/$modname.mod.php"))
						unlink("$extractDir/includes/$modname.mod.php");
					else $error=1;
				}
				else $error=1;
			}
			else $error=1;
			if(is_dir("$extractDir/templates"))
				$this->recursiveDirCopy("$extractDir/templates",$_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']);
			else $error=1;
			$this->recursiveDirDelete($extractDir);
		}
		else $error=1;
		return $error;
	}
	
	// Template Administration
	public function templateAdmin($content,$configs)
	{
		$basedir=$_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"];
		if($_GET['func']=="save")
		{
			$f=@fopen(((preg_match("/([0-9])_(.*)/si",@($_POST['filename']),$match) && $match)?(($match[1]==0)?$basedir.$configs['path']['templates']."/${match[2]}":$basedir.$configs['path']['mod_templates']."/${match[2]}"):""),"w");
			@fwrite($f,$_POST['filecontent']);
			@fclose($f);
		}
		$content=str_replace("{TEMPFILES}",$this->genDirTreeOut($this->createDirTree($basedir.$configs['path']['templates']),$basedir.$configs['path']['templates'],"&nbsp;&nbsp;","<option value=\"{TYPE}{PREVDIRS}{FILE}\"{SELECTED}>{DEPTH}{FILE}</option>\n","[DIR] ",0,"",1),$content);
		$content=str_replace("{MODTEMPFILES}",$this->genDirTreeOut($this->createDirTree($basedir.$configs['path']['mod_templates']),$basedir.$configs['path']['mod_templates'],"&nbsp;&nbsp;","<option value=\"{TYPE}{PREVDIRS}{FILE}\"{SELECTED}>{DEPTH}{FILE}</option>\n","[DIR] ",1,"",1),$content);
		$content=str_replace("{TEMPFILE}",@$_POST['filename'],$content);
		$content=str_replace("{FILECONTENT}",((preg_match("/([0-9])_(.*)/si",@($_POST['filename']),$match) && $match)?(($match[1]==0)?$this->simpleFilter(file_get_contents($basedir.$configs['path']['templates']."/${match[2]}"),0):$this->simpleFilter(file_get_contents($basedir.$configs['path']['mod_templates']."/${match[2]}"),0)):""),$content);
		return $content;
	}
	
	// Widget Administration
	public function widgetAdmin($content,$configs)
	{
		switch($_GET['func'])
		{
			case "delete":
				$this->delWidget($configs);
				break;
			case "upload":
				$this->uploadWidget($configs);
				break;
		}
		$widgetsTxt="";
		$widgetsArr=array();
		$widgetsDir=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/widgets/";
		$dir=opendir($widgetsDir);
		while(@($file=readdir($dir)))
			if(preg_match("/(.*)\.widget\.php$/si",$file,$matches) && (@$matches))
				$widgetsArr[]=$matches[1];
		foreach($widgetsArr as $widget)
			$widgetsTxt.="<tr><td><input type=\"checkbox\" name=\"delete_".htmlspecialchars($widget)."\" value=\"".htmlspecialchars($widget)."\"/>$widget</td><td>&#123;WIDGET_$widget&#125;</td><td>".date("m/d/Y",filemtime($widgetsDir.$widget.".widget.php"))."</td></tr>";
		return str_replace("{WIDGETS}",$widgetsTxt,$content);
	}
	public function delWidget($configs)
	{
		$keys=array_keys($_POST);
		$delKeys=preg_grep("/delete_(.*)/si",$keys);
		foreach($delKeys as $delKey)
		{
			preg_match("/delete_(.*)/si",$delKey,$matches);
			$widgetname=$matches[1];
			$widget=$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/widgets/$widgetname.widget.php";
			@unlink($widget);
		}
	}
	public function uploadWidget($configs)
	{
		$error=0;
		$filename=$_FILES['rfile']['name'];
		$tmpname=$_FILES['rfile']['tmp_name'];
		if(preg_match("/([^\/])*.widget.php/si",$filename,$match) && $match[0]!="")
			$widgetname=str_replace(".widget.php","",$match[0]);
		else $error=1;
		if(copy($tmpname,$_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/widgets/$widgetname.widget.php"))
			unlink($tmpname);
		else $error=1;
		return $error;
	}

	
	// Theme Management
	public function themeMgr($content,$configs)
	{
		$content=str_replace("{THEMES}",$this->genDirTreeOut($this->createDirTree($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['themes'],1),$_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['themes'],"&nbsp","<option value=\"{FILE}\"{SELECTED}>{FILE}</option><br/>","",2),$content);
		if($_GET['func']=="setTheme")
		{
			$origconfig=file_get_contents($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/config.inc.php");
			$f=@fopen($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/config.inc.php","w");
			@fwrite($f,str_replace("\$configs[\"default_theme\"]=\"${configs['default_theme']}\";","\$configs[\"default_theme\"]=\"${_POST['filename']}\";",$origconfig));
			@fclose($f);
			include("includes/config.inc.php");
			$_GET=array("act"=>"themeMgr");
			$_POST=array();
			$content=$this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['templates']."/overall.template","adminCP");
		}
		else if($_GET['func']=="upload")
		{
			$error=0;
			$zipname=$_FILES['rfile']['name'];
			$tmpname=$_FILES['rfile']['tmp_name'];
			if(preg_match("/([^\/])*.zip/si",$zipname,$match) && $match[0]!="")
				$themename=str_replace(".zip","",$match[0]);
			else $error=1;
			$extractDir=$configs['path']['tmpdir']."/$themename";
			$zip=new ZipArchive();
			if($zip->open($tmpname) && $error==0)
			{
				$zip->extractTo($extractDir);
				$zip->close();
				if(is_dir("$extractDir/$themename"))
				{
					$this->recursiveDirCopy($extractDir,$_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['themes']);
				}
			}
		}
		return $content;
	}
	
	// Test Environment
	function testEnvironment($content,$configs)
	{
		$results=array();
		$conn=@mysql_connect($ss->host,$ss->user,$ss->pass);
		if(!(is_dir($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['templates'])))
			$results[]="<ul>Templates directory does not exist.  How are you seeing this?";
		if(!(is_dir($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/mods/")))
			$results[]="<ul>Modules directory does not exist, therefore no mods will be used.";
		if(!(is_file($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/config.inc.php"))) // Check if config file exists, if not flag it
			$results[]="<ul>Configuration file does not exist.";
		if(!(@mysql_connect($configs['database']['host'],$configs['database']['username'],$configs['database']['password']))) // Check if the MySQL credentials are correct, if not flag it
			$results[]="<ul>MySQL credentials are incorrect.";
		if(!(class_exists("ZipArchive"))) // Check if ZipArchive is installed, if not flag it
			$results[]="<ul>ZipArchive is not installed, you will not be able to upload templates or modules.";
		if(!(is_writable($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/config.inc.php")))
			$results[]="<ul>Config file is not writable, you will not be able to edit it from the admin control panel.";
		if(!(is_writable($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['themes'])))
			$results[]="<ul>Theme directory is not writable, you will not be able to upload new themes.";
		if(!(is_writable($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates'])))
			$results[]="<ul>Module templates directory is not writable, any modules you upload will not have the required templates.";
		if(!(is_writable($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."includes/mods")))
			$results[]="<ul>Modules directory is not writable, you will not be able to upload any modules.";
		@mysql_close($conn);
		if($results==array())
			$results[]="No dependency or file permission errors, everything should work fine.";
		return str_replace("{DEPENDENCIES}",implode("</ul>",$results),$content);
	}
}
?>
