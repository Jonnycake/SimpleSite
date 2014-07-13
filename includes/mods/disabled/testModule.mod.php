<?php
if(SIMPLESITE!=1)
	die("Can't access this file directly.");
class testModule extends SimpleDisplay implements simpleModule // Allows for access to readTemplate
{
	// MODINFO_* constant will return blank if you don't have this
	public static $info=array( "author"  => "Jon Stockton",
						"name"    => "Test Module",
						"version" => "0.1",
						"date"    => "September 4, 2012"
					  );
					  
	// Required functions
	public function choosePage() // Return blank for overall template otherwise beginning of template name
	{
		return "";
	}
	public function sideparse($content) // Do whatever extra parsing you want to
	{
		return str_replace("{TEST}","Hello World!",$content);
	}
	public function isInstalled() // Check if the mod is actually installed
	{
		return TRUE;
	}
	public function install() // Install the mod
	{
		return TRUE;
	}
	public function getContent($configs=array()) // What you want to return for {CONTENT} constant
	{
		return $this->readTemplate($_SERVER['DOCUMENT_ROOT'].$configs["path"]["root"].$configs["path"]["mod_templates"]."/testModule.template","testModule");
	}
}
?>
