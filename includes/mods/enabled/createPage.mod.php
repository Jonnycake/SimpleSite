<?php
if(SIMPLESITE!=1)
	die("Can't access this file directly.");

class createPage extends SimpleModule
{
	private static $funcPerformed=0;
	public static $info=array(  "author"  => "Jon Stockton",
	                            "name"    => "ViewPage Module",
	                            "version" => "0.1",
	                            "date"    => "April 9, 2012"
	);
					  
	public function sideparse($content)
	{
		return $content;
	}
	public function isInstalled($configs=array())
	{
		return file_exists($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."templates/pages");
	}
	public function install($configs=array())
	{
		mkdir($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."templates/pages");
		chmod($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."templates/pages", 0777);
		return TRUE;
	}
	public function uninstall($configs=array())
	{
		return true;
	}
	public function getContent($configs=array())
	{
		if(!@$_SESSION['is_admin'])
		{
			SimpleDebug::logInfo("Possible security incident, request for createPage from: ".$_SERVER['REMOTE_ADDR']);
			return "You are not an administrator, did you hit this page on accident?";
		}
		else
		{
			if (!self::$funcPerformed && isset($_GET['func']))
			{
				self::$funcPerformed=1;
				switch(@$_GET['func'])
				{
					case "save":
						SimpleDebug::logInfo("Saving ${_POST['pageName']} with base64 ${_POST['content']}");
						$f=fopen($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."templates/pages/${_POST['pageName']}.template", "w");
						fwrite($f, base64_decode($_POST['content']));
						fclose($f);
						return null;
					case "delete":
						SimpleDebug::logInfo("Deleting ${_POST['pageName']}...");
						unlink($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."templates/pages/${_POST['pageName']}.template");
						return null;
					case "open":
						SimpleDebug::logInfo("Opening ${_POST['pageName']} for editing.");
						echo file_get_contents($_SERVER['DOCUMENT_ROOT'].$configs['path']['root']."templates/pages/${_GET['pageName']}.template");
						return null;
					default:
						break;
				}
			}
			else if(isset($_GET['func']))
				return null;
			return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs['path']['root'].$configs['path']['mod_templates']."/createPage.template");
		}
	}
}
?>
